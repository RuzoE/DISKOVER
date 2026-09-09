<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_responses_carry_the_baseline_security_headers(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/dashboard');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
    }

    public function test_hsts_is_absent_over_plain_http(): void
    {
        $response = $this->get('/login');

        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_hsts_is_present_over_https(): void
    {
        $response = $this->get('https://localhost/login');

        $response->assertHeader('Strict-Transport-Security');
    }
}
