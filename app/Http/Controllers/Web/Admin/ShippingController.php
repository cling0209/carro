<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingComunaWeightRate;
use App\Models\ShippingRegionRate;
use App\Models\ShippingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingController extends Controller
{
    public function index(Request $request): View
    {
        ShippingRegionRate::syncFromChileRegions();
        ShippingComunaWeightRate::syncAllComunasFromChileData();

        $regionComunas = ShippingComunaWeightRate::chileRegionComunasExcludingRm();
        $regions = array_keys($regionComunas);

        $selectedRegion = $request->query('region', $regions[0] ?? '');
        $selectedComuna = $request->query('comuna');

        if ($selectedRegion !== '' && ! array_key_exists($selectedRegion, $regionComunas)) {
            $selectedRegion = $regions[0] ?? '';
        }

        $comunas = $regionComunas[$selectedRegion] ?? [];

        if ($selectedComuna === null || ! in_array($selectedComuna, $comunas, true)) {
            $selectedComuna = $comunas[0] ?? null;
        }

        $comunaRates = collect();

        if ($selectedRegion !== '' && $selectedComuna !== null) {
            $comunaRates = ShippingComunaWeightRate::query()
                ->where('region', $selectedRegion)
                ->where('comuna', $selectedComuna)
                ->orderBy('sort_order')
                ->orderBy('min_weight_kg')
                ->get();
        }

        return view('admin.shipping.index', [
            'rmFlatRate' => ShippingSetting::getFloat('rm_flat_rate', 3990),
            'defaultProductWeight' => ShippingSetting::getFloat('default_product_weight_kg', 1.0),
            'regionRates' => ShippingRegionRate::query()->orderBy('region')->get(),
            'regionComunas' => $regionComunas,
            'selectedRegion' => $selectedRegion,
            'selectedComuna' => $selectedComuna,
            'comunaRates' => $comunaRates,
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

    public function updateRegionRates(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'regions' => ['required', 'array'],
            'regions.*.flat_rate' => ['required', 'numeric', 'min:0'],
        ]);

        foreach ($data['regions'] as $id => $row) {
            $regionRate = ShippingRegionRate::query()->find($id);

            if (! $regionRate) {
                continue;
            }

            $regionRate->update([
                'flat_rate' => $row['flat_rate'],
                'is_active' => ! empty($row['is_active']),
            ]);
        }

        return redirect()
            ->route('admin.shipping.index')
            ->with('success', 'Tarifas fijas por región actualizadas.');
    }

    public function storeRate(Request $request): RedirectResponse
    {
        $data = $this->validatedRate($request);
        ShippingComunaWeightRate::create($data);

        return redirect()
            ->route('admin.shipping.index', [
                'region' => $data['region'],
                'comuna' => $data['comuna'],
            ])
            ->with('success', 'Tramo de peso creado.');
    }

    public function updateRate(Request $request, ShippingComunaWeightRate $rate): RedirectResponse
    {
        $data = $this->validatedRate($request, $rate);
        $rate->update($data);

        return redirect()
            ->route('admin.shipping.index', [
                'region' => $rate->region,
                'comuna' => $rate->comuna,
            ])
            ->with('success', 'Tramo de peso actualizado.');
    }

    public function destroyRate(ShippingComunaWeightRate $rate): RedirectResponse
    {
        $region = $rate->region;
        $comuna = $rate->comuna;
        $rate->delete();

        return redirect()
            ->route('admin.shipping.index', compact('region', 'comuna'))
            ->with('success', 'Tramo de peso eliminado.');
    }

    protected function validatedRate(Request $request, ?ShippingComunaWeightRate $existing = null): array
    {
        $data = $request->validate([
            'region' => ['required', 'string', 'max:80'],
            'comuna' => ['required', 'string', 'max:80'],
            'label' => ['required', 'string', 'max:120'],
            'min_weight_kg' => ['required', 'numeric', 'min:0'],
            'max_weight_kg' => ['nullable', 'numeric', 'gt:min_weight_kg'],
            'price' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! ShippingComunaWeightRate::isValidComuna($data['region'], $data['comuna'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'comuna' => 'La comuna no pertenece a la región seleccionada.',
            ]);
        }

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
