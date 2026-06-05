<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\WebpayGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentWebController extends Controller
{
    public function __construct(protected WebpayGateway $webpay) {}

    public function return(Request $request): View|RedirectResponse
    {
        $token = $request->input('token_ws') ?? $request->input('TBK_TOKEN');

        if (! $token) {
            return redirect()->route('home')->with('error', 'Pago cancelado o sesión inválida.');
        }

        try {
            $result = $this->webpay->commitTransaction($token);
            $order = Order::where('uuid', $result['order_uuid'])->firstOrFail();

            if ($result['approved']) {
                return view('shop.order-success', [
                    'order' => $order->load(['items', 'paymentTransactions']),
                    'payment' => $result,
                    'cartCount' => 0,
                ]);
            }

            return view('shop.order-failed', [
                'order' => $order,
                'cartCount' => 0,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('home')->with('error', 'Error al confirmar el pago.');
        }
    }
}
