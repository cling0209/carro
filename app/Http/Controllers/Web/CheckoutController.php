<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payments\WebpayGateway;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected OrderService $orderService,
        protected WebpayGateway $webpay,
        protected ShippingService $shippingService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $cart = $this->cartService->resolve($request);
        $formatted = $this->cartService->formatCart($cart);

        if ($formatted['item_count'] === 0) {
            return redirect()->route('cart.index')->with('error', 'Tu carro está vacío.');
        }

        $regions = File::exists(database_path('data/chile_regions.json'))
            ? json_decode(File::get(database_path('data/chile_regions.json')), true)
            : [];

        return view('shop.checkout', [
            'formatted' => $formatted,
            'regions' => $regions,
            'cartCount' => $formatted['item_count'],
        ]);
    }

    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'region' => ['required', 'string', 'max:80'],
            'comuna' => ['nullable', 'string', 'max:80'],
        ]);

        try {
            $cart = $this->cartService->resolve($request);
            $formatted = $this->cartService->formatCart($cart);

            if ($formatted['item_count'] === 0) {
                return response()->json(['message' => 'El carrito está vacío.'], 422);
            }

            $quote = $this->shippingService->quote(
                $cart,
                $data['region'],
                $data['comuna'] ?? null,
            );

            return response()->json([
                'subtotal' => $formatted['subtotal'],
                'shipping' => $quote,
                'total' => round($formatted['subtotal'] + $quote['amount'], 2),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'region' => ['required', 'string', 'max:80'],
            'comuna' => ['required', 'string', 'max:80'],
            'street' => ['required', 'string', 'max:180'],
            'street_number' => ['nullable', 'string', 'max:20'],
            'apartment' => ['nullable', 'string', 'max:40'],
        ]);

        try {
            $cart = $this->cartService->resolve($request);
            $order = $this->orderService->createFromCart($cart, $data, $request->user());
            $payment = $this->webpay->createTransaction($order);

            return redirect()->away($payment['url'].'?token_ws='.$payment['token']);
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->withInput()->with('error', 'No se pudo procesar el pedido. Intenta nuevamente.');
        }
    }
}
