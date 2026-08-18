<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_forwarded_https_is_used_for_generated_login_url(): void
    {
        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'pos.example.test',
            'X-Forwarded-Port' => '443',
        ])->get('http://127.0.0.1/login')
            ->assertOk()
            ->assertSee('action="https://pos.example.test/login"', false);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_cashier_cannot_open_settings(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);

        $this->actingAs($cashier)->get('/settings')->assertForbidden();
    }

    public function test_owner_can_open_settings(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);

        $this->actingAs($owner)->get('/settings')->assertOk();
    }
}
