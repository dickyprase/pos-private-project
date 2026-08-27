<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ShiftResource;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends ApiController
{
    public function bootstrap(Request $request): JsonResponse
    {
        $settings = StoreSetting::current();
        $user = $request->user();
        $shift = $user->activeShift();

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role->value,
            ],
            'store' => [
                'name' => $settings->store_name,
                'tax_enabled' => (bool) $settings->tax_enabled,
                'tax_rate' => $settings->tax_enabled ? (float) $settings->tax_rate : 0,
                'service_charge_rate' => 0,
            ],
            'payments' => [
                'qris_enabled' => (bool) $settings->qris_enabled && filled($settings->qris_image_path),
                'qris_image_url' => $settings->qris_image_path ? url('/storage/'.$settings->qris_image_path) : null,
            ],
            'shift' => $shift ? new ShiftResource($shift) : null,
        ]);
    }
}
