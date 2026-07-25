<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NominatimGeocoder
{
    private const BASE_URL = 'https://nominatim.openstreetmap.org';

    private const CHILE_VIEWBOX = '-76.0,-56.0,-66.0,-17.0';

    /**
     * @return array{lat: float, lng: float, display_name: string}|null
     */
    public function searchPlace(string $query): ?array
    {
        $query = trim($query);

        if ($query === '') {
            return null;
        }

        $cacheKey = 'nominatim:search:'.md5(mb_strtolower($query));

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($query) {
            $response = Http::timeout(8)
                ->withHeaders($this->headers())
                ->acceptJson()
                ->get(self::BASE_URL.'/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'cl',
                    'viewbox' => self::CHILE_VIEWBOX,
                    'bounded' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $first = $response->json('0');

            if (! is_array($first) || ! isset($first['lat'], $first['lon'])) {
                return null;
            }

            return [
                'lat' => (float) $first['lat'],
                'lng' => (float) $first['lon'],
                'display_name' => (string) ($first['display_name'] ?? $query),
            ];
        });
    }

    /**
     * @return array{
     *     lat: float,
     *     lng: float,
     *     street: ?string,
     *     street_number: ?string,
     *     comuna: ?string,
     *     region: ?string,
     *     display_name: string
     * }|null
     */
    public function reverse(float $lat, float $lng): ?array
    {
        if (! $this->isInChile($lat, $lng)) {
            return null;
        }

        $cacheKey = 'nominatim:reverse:'.round($lat, 5).':'.round($lng, 5);

        return Cache::remember($cacheKey, now()->addDays(3), function () use ($lat, $lng) {
            $response = Http::timeout(8)
                ->withHeaders($this->headers())
                ->acceptJson()
                ->get(self::BASE_URL.'/reverse', [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'zoom' => 18,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (! is_array($data) || ! isset($data['lat'], $data['lon'])) {
                return null;
            }

            $address = is_array($data['address'] ?? null) ? $data['address'] : [];

            return [
                'lat' => (float) $data['lat'],
                'lng' => (float) $data['lon'],
                'street' => $this->firstString($address, ['road', 'pedestrian', 'path', 'residential', 'street']),
                'street_number' => $this->firstString($address, ['house_number']),
                'comuna' => $this->firstString($address, ['city', 'town', 'municipality', 'village', 'suburb']),
                'region' => $this->firstString($address, ['state', 'region']),
                'display_name' => (string) ($data['display_name'] ?? ''),
            ];
        });
    }

    public function isInChile(float $lat, float $lng): bool
    {
        return $lat <= -17.0 && $lat >= -56.0 && $lng >= -76.0 && $lng <= -66.0;
    }

    /**
     * @param  array<string, mixed>  $address
     * @param  list<string>  $keys
     */
    protected function firstString(array $address, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $address[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        $appUrl = (string) config('app.url', 'https://tienda.romulo.cl');

        return [
            'User-Agent' => 'TiendaRomulo/1.0 ('.$appUrl.'; checkout-map)',
            'Accept-Language' => 'es-CL,es',
        ];
    }
}
