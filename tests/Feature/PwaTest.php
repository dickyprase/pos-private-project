<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaTest extends TestCase
{
    public function test_pwa_artifacts_are_publicly_available(): void
    {
        $this->assertFileExists(public_path('manifest.webmanifest'));

        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('/pos', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('icons/icon-192.png'));
        $this->assertFileExists(public_path('icons/icon-512.png'));
    }

    public function test_pos_layout_registers_manifest_and_service_worker_bundle(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/pos.blade.php'));
        $javascript = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('manifest.webmanifest', $layout);
        $this->assertStringContainsString('apple-touch-icon', $layout);
        $this->assertStringContainsString("import './pwa';", $javascript);
    }
}
