<?php

namespace Tests\Unit;

use App\Support\CartSessionCookie;
use Tests\TestCase;

class CartSessionCookieTest extends TestCase
{
    public function test_cookie_is_http_only_with_lax_same_site(): void
    {
        $cookie = CartSessionCookie::make('550e8400-e29b-41d4-a716-446655440000');

        $this->assertSame('cart_session', $cookie->getName());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
    }
}
