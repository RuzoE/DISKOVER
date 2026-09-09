<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Comprobación de humo: la aplicación arranca y el enrutado base responde.
 */
class SmokeTest extends TestCase
{
    public function test_root_redirects_to_the_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_health_endpoint_is_up(): void
    {
        $this->get('/up')->assertOk();
    }
}
