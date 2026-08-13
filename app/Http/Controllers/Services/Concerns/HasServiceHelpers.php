<?php

namespace App\Http\Controllers\Services\Concerns;

use App\Models\Service;
use App\Models\ServiceTracking;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HasServiceHelpers
{
    private function generateQrToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Service::where('qr_token', $token)->exists() || ServiceTracking::where('qr_token', $token)->exists());

        return $token;
    }

    private function storeEvidence($file): ?string
    {
        if (! $file) {
            return null;
        }

        return Storage::disk('public')->putFile('evidencias', $file);
    }
}
