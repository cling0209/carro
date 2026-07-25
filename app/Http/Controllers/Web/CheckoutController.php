<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CartService;
use App\Services\CustomerAddressService;
use App\Services\NominatimGeocoder;
use App\Services\OrderService;
use App\Services\Payments\WebpayGateway;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected OrderService $orderService,
        protected WebpayGateway $webpay,
        protected ShippingService $shippingService,
        protected CustomerAddressService $addressService,
        protected NominatimGeocoder $geocoder,
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

        $user = $request->user();
        $saved = $this->addressService->checkoutDefaults($user);
        $defaults = [];

        foreach ([
            'customer_name', 'email', 'recipient_name', 'phone',
            'region', 'comuna', 'street', 'street_number', 'apartment',
        ] as $field) {
            $defaults[$field] = old($field, $saved[$field] ?? '');
        }

        $defaults['latitude'] = old('latitude', $saved['latitude'] ?? null);
        $defaults['longitude'] = old('longitude', $saved['longitude'] ?? null);

        return view('shop.checkout', [
            'formatted' => $formatted,
            'regions' => $regions,
            'cartCount' => $formatted['item_count'],
            'defaults' => $defaults,
            'isLoggedIn' => $user !== null,
            'userName' => $user?->name,
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

    public function geocode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'region' => ['required', 'string', 'max:80'],
            'comuna' => ['required', 'string', 'max:80'],
        ]);

        $place = $this->geocoder->searchPlace($data['comuna'].', '.$data['region'].', Chile');

        if (! $place) {
            return response()->json([
                'lat' => -33.4489,
                'lng' => -70.6693,
                'display_name' => $data['comuna'].', '.$data['region'],
                'fallback' => true,
            ]);
        }

        return response()->json([
            'lat' => $place['lat'],
            'lng' => $place['lng'],
            'display_name' => $place['display_name'],
            'fallback' => false,
        ]);
    }

    public function reverseGeocode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-56,-17'],
            'lng' => ['required', 'numeric', 'between:-76,-66'],
        ]);

        $place = $this->geocoder->reverse((float) $data['lat'], (float) $data['lng']);

        if (! $place) {
            return response()->json(['message' => 'No se pudo ubicar ese punto. Prueba más cerca de una calle.'], 422);
        }

        return response()->json($place);
    }

    public function geocodeAddress(Request $request): JsonResponse
    {
        $data = $request->validate([
            'region' => ['required', 'string', 'max:80'],
            'comuna' => ['required', 'string', 'max:80'],
            'street' => ['required', 'string', 'max:180'],
            'street_number' => ['nullable', 'string', 'max:20'],
        ]);

        $parts = array_filter([
            trim($data['street'].' '.trim((string) ($data['street_number'] ?? ''))),
            $data['comuna'],
            $data['region'],
            'Chile',
        ]);

        $place = $this->geocoder->searchPlace(implode(', ', $parts));

        if (! $place) {
            return response()->json([
                'message' => 'No encontramos esa dirección en el mapa. Ajusta calle/número o marca el pin manualmente.',
            ], 422);
        }

        return response()->json([
            'lat' => $place['lat'],
            'lng' => $place['lng'],
            'display_name' => $place['display_name'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'region' => ['required', 'string', 'max:80'],
            'comuna' => ['required', 'string', 'max:80'],
            'street' => ['required', 'string', 'max:180'],
            'street_number' => ['nullable', 'string', 'max:20'],
            'apartment' => ['nullable', 'string', 'max:40'],
            'latitude' => ['required', 'numeric', 'between:-56,-17'],
            'longitude' => ['required', 'numeric', 'between:-76,-66'],
            'address_synced' => ['required', 'accepted'],
            'create_account' => ['nullable', 'boolean'],
            'password' => ['nullable', 'required_if:create_account,1', 'confirmed', Password::min(8)],
        ];

        if (! $request->user() && $request->boolean('create_account')) {
            $rules['email'][] = 'unique:users,email';
        }

        $data = $request->validate($rules, [
            'latitude.required' => 'Marca tu ubicación en el mapa para continuar.',
            'longitude.required' => 'Marca tu ubicación en el mapa para continuar.',
            'address_synced.accepted' => 'La calle debe coincidir con el pin. Usa “Buscar en el mapa” o vuelve a marcar el pin.',
        ]);

        if (! $this->geocoder->isInChile((float) $data['latitude'], (float) $data['longitude'])) {
            return redirect()->back()->withInput()->with('error', 'La ubicación debe estar dentro de Chile.');
        }

        try {
            if (! $request->user() && $request->boolean('create_account')) {
                $user = User::create([
                    'name' => $data['customer_name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => 'customer',
                ]);

                Auth::login($user);
                $request->session()->regenerate();
            }

            $user = $request->user();
            $this->orderService->cancelStalePendingOrders($user, $data['email']);

            $cart = $this->cartService->resolve($request);
            $order = $this->orderService->createFromCart($cart, $data, $user);
            $request->session()->put('pending_order_uuid', $order->uuid);

            if ($user) {
                $this->addressService->syncDefaultFromShipping($user, $data);

                if ($user->name !== $data['customer_name']) {
                    $user->update(['name' => $data['customer_name']]);
                }
            }

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
