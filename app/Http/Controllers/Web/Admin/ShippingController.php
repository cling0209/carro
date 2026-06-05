<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingSetting;
use App\Models\ShippingWeightRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingController extends Controller
{
    public function index(): View
    {
        return view('admin.shipping.index', [
            'rmFlatRate' => ShippingSetting::getFloat('rm_flat_rate', 3990),
            'defaultProductWeight' => ShippingSetting::getFloat('default_product_weight_kg', 1.0),
            'rates' => ShippingWeightRate::query()
                ->orderBy('sort_order')
                ->orderBy('min_weight_kg')
                ->get(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rm_flat_rate' => ['required', 'numeric', 'min:0'],
            'default_product_weight_kg' => ['required', 'numeric', 'min:0.001'],
        ]);

        ShippingSetting::setValue('rm_flat_rate', $data['rm_flat_rate']);
        ShippingSetting::setValue('default_product_weight_kg', $data['default_product_weight_kg']);

        return redirect()
            ->route('admin.shipping.index')
            ->with('success', 'Configuración de envío actualizada.');
    }

    public function storeRate(Request $request): RedirectResponse
    {
        $data = $this->validatedRate($request);
        ShippingWeightRate::create($data);

        return redirect()
            ->route('admin.shipping.index')
            ->with('success', 'Tramo de peso creado.');
    }

    public function updateRate(Request $request, ShippingWeightRate $rate): RedirectResponse
    {
        $rate->update($this->validatedRate($request));

        return redirect()
            ->route('admin.shipping.index')
            ->with('success', 'Tramo de peso actualizado.');
    }

    public function destroyRate(ShippingWeightRate $rate): RedirectResponse
    {
        $rate->delete();

        return redirect()
            ->route('admin.shipping.index')
            ->with('success', 'Tramo de peso eliminado.');
    }

    protected function validatedRate(Request $request): array
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'min_weight_kg' => ['required', 'numeric', 'min:0'],
            'max_weight_kg' => ['nullable', 'numeric', 'gt:min_weight_kg'],
            'price' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
