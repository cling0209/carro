<?php

if (! function_exists('clp_amount')) {
    function clp_amount(float|int|string|null $amount): int
    {
        return (int) round((float) $amount);
    }
}

if (! function_exists('clp')) {
    function clp(float|int|string|null $amount): string
    {
        return '$'.number_format(clp_amount($amount), 0, ',', '.');
    }
}

if (! function_exists('product_image')) {
    function product_image(?\App\Models\Product $product): string
    {
        if (! $product) {
            return '';
        }

        return $product->resolveImageUrl();
    }
}

if (! function_exists('product_image_loading')) {
    function product_image_loading(): string
    {
        return asset('images/loading.svg');
    }
}

if (! function_exists('product_image_placeholder')) {
    function product_image_placeholder(): string
    {
        return asset('images/no-image.svg');
    }
}

if (! function_exists('order_status_label')) {
    function order_status_label(?string $status): string
    {
        return match ($status) {
            'pending_payment' => 'Pendiente de pago',
            'paid' => 'Pagado',
            'processing' => 'En preparación',
            'shipped' => 'Enviado',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            'payment_failed' => 'Pago fallido',
            default => $status ?? '—',
        };
    }
}

if (! function_exists('payment_status_label')) {
    function payment_status_label(?string $status): string
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'failed' => 'Fallido',
            'refunded' => 'Reembolsado',
            default => $status ?? '—',
        };
    }
}

if (! function_exists('payment_transaction_status_label')) {
    function payment_transaction_status_label(?string $status): string
    {
        return match ($status) {
            'created' => 'Iniciada',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            default => $status ?? '—',
        };
    }
}

if (! function_exists('document_type_label')) {
    function document_type_label(?string $type): string
    {
        return match ($type) {
            'boleta' => 'Boleta',
            'factura' => 'Factura',
            default => $type ?? '—',
        };
    }
}

if (! function_exists('chilean_rut_normalize')) {
    /** Solo dígitos + DV (K), sin puntos ni guión. */
    function chilean_rut_normalize(?string $rut): string
    {
        return strtoupper(preg_replace('/[^0-9kK]/', '', (string) $rut) ?? '');
    }
}

if (! function_exists('chilean_rut_format')) {
    /** Formato 12.345.678-9 */
    function chilean_rut_format(?string $rut): string
    {
        $clean = chilean_rut_normalize($rut);
        if (strlen($clean) < 2) {
            return (string) $rut;
        }

        $dv = substr($clean, -1);
        $number = substr($clean, 0, -1);

        return number_format((int) $number, 0, '', '.').'-'.$dv;
    }
}

if (! function_exists('chilean_rut_is_valid')) {
    function chilean_rut_is_valid(?string $rut): bool
    {
        $clean = chilean_rut_normalize($rut);
        if (strlen($clean) < 2 || strlen($clean) > 9) {
            return false;
        }

        $dv = substr($clean, -1);
        $number = substr($clean, 0, -1);

        if (! ctype_digit($number)) {
            return false;
        }

        $sum = 0;
        $multiplier = 2;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $sum += (int) $number[$i] * $multiplier;
            $multiplier = $multiplier === 7 ? 2 : $multiplier + 1;
        }

        $remainder = 11 - ($sum % 11);
        $expected = match ($remainder) {
            11 => '0',
            10 => 'K',
            default => (string) $remainder,
        };

        return $dv === $expected;
    }
}
