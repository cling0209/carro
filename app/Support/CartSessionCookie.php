<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\Cookie;

class CartSessionCookie
{
    public const NAME = 'cart_session';

    public const LIFETIME_MINUTES = 60 * 24 * 30;

    public static function make(string $sessionId): Cookie
    {
        $secure = config('session.secure');
        if ($secure === null) {
            $secure = request()->isSecure();
        }

        return cookie(
            self::NAME,
            $sessionId,
            self::LIFETIME_MINUTES,
            '/',
            config('session.domain'),
            (bool) $secure,
            true,
            false,
            config('session.same_site', 'lax'),
        );
    }
}
