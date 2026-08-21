<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaLoginUiTest extends TestCase
{
    public function test_login_is_pwa_entrypoint_with_install_control(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('apple-touch-icon', false)
            ->assertSee('data-install-app', false)
            ->assertSee('Install aplikasi');

        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('/login', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('any maskable', $manifest['icons'][0]['purpose']);
    }

    public function test_login_has_clear_contextual_placeholders_and_accessible_fields(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk ke ruang kerja', false)
            ->assertSee('placeholder="Masukkan username atau email"', false)
            ->assertSee('placeholder="Masukkan password"', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('aria-label="Tampilkan password"', false)
            ->assertSee('name="login"', false)
            ->assertSee('name="password"', false);
    }
}
