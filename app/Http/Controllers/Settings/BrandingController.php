<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\BrandingUpdateRequest;
use App\Models\HospitalBranding;
use App\Services\HospitalBrandingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CIMA-040: hospital branding settings (ADR-005).
 * Authorization handled by the `permission:settings.branding` route middleware.
 */
class BrandingController extends Controller
{
    /**
     * Show the hospital branding settings form.
     */
    public function edit(): Response
    {
        $branding = HospitalBranding::resolve();

        return Inertia::render('settings/branding', [
            'branding' => [
                'name' => $branding?->name,
                'logoUrl' => $branding?->logoUrl(),
                'displayText' => $branding?->display_text,
                'defaultPreset' => $branding?->defaultPreset(),
                'allowedPresets' => $branding?->allowedPresets(),
            ],
        ]);
    }

    /**
     * Update the singleton branding row and the logo asset.
     */
    public function update(
        BrandingUpdateRequest $request,
        HospitalBrandingService $service,
    ): RedirectResponse {
        $service->update(
            HospitalBranding::resolve(),
            $request->validated(),
            $request->file('logo'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Branding updated.')]);

        return to_route('branding.edit');
    }
}
