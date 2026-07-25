<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;

class CustomerAddressService
{
    public function defaultForUser(User $user): ?Address
    {
        return $user->addresses()
            ->where('is_default', true)
            ->first()
            ?? $user->addresses()->latest()->first();
    }

    /**
     * Defaults del checkout: prioriza la última compra del usuario (todos los campos)
     * para que pueda confirmar o modificar sin volver a escribir todo.
     *
     * @return array{defaults: array<string, string|float|null>, source: 'last_order'|'address'|'profile'|null}
     */
    public function checkoutDefaults(?User $user): array
    {
        if (! $user) {
            return ['defaults' => [], 'source' => null];
        }

        $order = Order::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'pending_payment', 'payment_failed'])
            ->latest()
            ->first();

        if (! $order) {
            // Sin compra confirmada: usar cualquier pedido reciente con datos útiles.
            $order = Order::query()
                ->where('user_id', $user->id)
                ->latest()
                ->first();
        }

        if ($order) {
            return [
                'source' => 'last_order',
                'defaults' => [
                    'customer_name' => $order->customer_name ?: $user->name,
                    'email' => $order->customer_email ?: $user->email,
                    'document_type' => $order->document_type ?: 'boleta',
                    'billing_rut' => $order->billing_rut,
                    'billing_business_name' => $order->billing_business_name,
                    'billing_activity' => $order->billing_activity,
                    'recipient_name' => $order->shipping_recipient_name,
                    'phone' => $order->shipping_phone,
                    'region' => $order->shipping_region,
                    'comuna' => $order->shipping_comuna,
                    'street' => $order->shipping_street,
                    'street_number' => $order->shipping_street_number,
                    'apartment' => $order->shipping_apartment,
                    'latitude' => $order->shipping_latitude,
                    'longitude' => $order->shipping_longitude,
                ],
            ];
        }

        $address = $this->defaultForUser($user);

        if ($address) {
            return [
                'source' => 'address',
                'defaults' => [
                    'customer_name' => $user->name,
                    'email' => $user->email,
                    'recipient_name' => $address->recipient_name,
                    'phone' => $address->phone,
                    'region' => $address->region,
                    'comuna' => $address->comuna,
                    'street' => $address->street,
                    'street_number' => $address->street_number,
                    'apartment' => $address->apartment,
                    'latitude' => $address->latitude,
                    'longitude' => $address->longitude,
                ],
            ];
        }

        return [
            'source' => 'profile',
            'defaults' => [
                'customer_name' => $user->name,
                'email' => $user->email,
            ],
        ];
    }

    public function syncDefaultFromShipping(User $user, array $shipping): Address
    {
        $payload = [
            'label' => 'Principal',
            'recipient_name' => $shipping['recipient_name'],
            'phone' => $shipping['phone'],
            'region' => $shipping['region'],
            'comuna' => $shipping['comuna'],
            'street' => $shipping['street'],
            'street_number' => $shipping['street_number'] ?? null,
            'apartment' => $shipping['apartment'] ?? null,
            'latitude' => $shipping['latitude'] ?? null,
            'longitude' => $shipping['longitude'] ?? null,
            'is_default' => true,
        ];

        $address = $this->defaultForUser($user);

        if ($address) {
            $address->update($payload);
        } else {
            $address = $user->addresses()->create($payload);
        }

        $user->addresses()
            ->where('id', '!=', $address->id)
            ->update(['is_default' => false]);

        return $address->fresh();
    }
}
