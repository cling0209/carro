<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_web_pages_include_security_headers(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_admin_login_is_not_cacheable(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }
}
