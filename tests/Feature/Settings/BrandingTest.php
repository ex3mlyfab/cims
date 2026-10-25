<?php

namespace Tests\Feature\Settings;

use App\Models\HospitalBranding;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
 * CIMA-040: hospital branding settings (ADR-005).
 * Cover the permission boundary, singleton semantics and logo asset
 * validation rules.
 */
class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
    }

    protected function userWithRole(string $name): User
    {
        $this->seedRoles();
        $user = User::factory()->create();
        $user->assignRole($name);

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('branding.edit', absolute: false))
            ->assertRedirect(route('login', absolute: false));
    }

    public function test_users_without_permission_are_forbidden(): void
    {
        $user = $this->userWithRole('nurse');

        $this->actingAs($user)
            ->get(route('branding.edit', absolute: false))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('branding.update', absolute: false), [
                'name' => 'Sneaky Hospital',
                'default_preset' => 'clinical-indigo',
                'allowed_presets' => ['clinical-indigo'],
            ])
            ->assertForbidden();

        $this->assertSame(0, HospitalBranding::query()->count());
    }

    public function test_hospital_administrator_and_super_admin_can_view_form(): void
    {
        foreach (['hospital administrator', 'super admin'] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)
                ->get(route('branding.edit', absolute: false))
                ->assertOk()
                ->assertSee('Hospital branding');
        }
    }

    public function test_update_creates_the_singleton_row(): void
    {
        Storage::fake('public');
        $user = $this->userWithRole('super admin');

        $response = $this->actingAs($user)->put(
            route('branding.update', absolute: false),
            [
                'name' => 'St. Example Hospital',
                'display_text' => 'Emergency Department Queue',
                'default_preset' => 'warm-amber',
                'allowed_presets' => ['clinical-indigo', 'warm-amber'],
            ],
        );

        $response->assertRedirect(route('branding.edit'));
        $this->assertSame(1, HospitalBranding::query()->count());

        $branding = HospitalBranding::query()->firstOrFail();
        $this->assertSame('St. Example Hospital', $branding->name);
        $this->assertSame('Emergency Department Queue', $branding->display_text);
        $this->assertSame('warm-amber', $branding->default_preset);
        $this->assertSame(['clinical-indigo', 'warm-amber'], $branding->allowed_presets);

        // Re-saving must keep singleton semantics.
        $this->actingAs($user)->put(route('branding.update', absolute: false), [
            'name' => 'Renamed Hospital',
            'default_preset' => 'clinical-indigo',
        ])->assertRedirect(route('branding.edit'));
        $this->assertSame('Renamed Hospital', $branding->refresh()->name);
        $this->assertSame(1, HospitalBranding::query()->count());
    }

    public function test_update_validates_fields(): void
    {
        $user = $this->userWithRole('super admin');

        $this->actingAs($user)->put(route('branding.update', absolute: false), [
            'name' => '',
            'default_preset' => 'not-a-preset',
            'allowed_presets' => ['clinical-indigo', 'nope'],
        ])->assertSessionHasErrors(['name', 'default_preset', 'allowed_presets.1']);

        $this->assertSame(0, HospitalBranding::query()->count());
    }

    public function test_valid_logo_is_stored_and_replaces_previous(): void
    {
        Storage::fake('public');
        $user = $this->userWithRole('super admin');

        $this->actingAs($user)->put(route('branding.update', absolute: false), [
            'name' => 'Logo Hospital',
            'default_preset' => 'clinical-indigo',
            'logo' => UploadedFile::fake()->image('logo.png', 64, 64),
        ])->assertRedirect(route('branding.edit'));

        $branding = HospitalBranding::query()->firstOrFail();
        $this->assertNotNull($branding->logo_path);
        Storage::disk('public')->assertExists($branding->logo_path);

        // A replacement removes the previous asset.
        $firstPath = $branding->logo_path;

        $this->actingAs($user)->put(route('branding.update', absolute: false), [
            'name' => 'Logo Hospital',
            'default_preset' => 'clinical-indigo',
            'logo' => UploadedFile::fake()->image('logo-2.png', 64, 64),
        ])->assertRedirect(route('branding.edit'));

        Storage::disk('public')->assertMissing($firstPath);
        $this->assertNotSame($firstPath, $branding->refresh()->logo_path);
    }

    public function test_logo_asset_validation_rejects_unsupported_files(): void
    {
        Storage::fake('public');
        $user = $this->userWithRole('super admin');

        $this->actingAs($user)->put(route('branding.update', absolute: false), [
            'name' => 'Bad Logo Hospital',
            'default_preset' => 'clinical-indigo',
            'logo' => UploadedFile::fake()->create('logo.txt', 10),
        ])->assertSessionHasErrors(['logo']);

        $this->assertSame(0, HospitalBranding::query()->count());
    }

    public function test_logo_size_validation_rejects_oversized_assets(): void
    {
        Storage::fake('public');
        $user = $this->userWithRole('super admin');

        $this->actingAs($user)->put(route('branding.update', absolute: false), [
            'name' => 'Big Logo Hospital',
            'default_preset' => 'clinical-indigo',
            'logo' => UploadedFile::fake()->image('logo.png')->size(800),
        ])->assertSessionHasErrors(['logo']);
    }

    public function test_remove_logo_deletes_the_stored_asset(): void
    {
        Storage::fake('public');
        $user = $this->userWithRole('super admin');

        $this->actingAs($user)->put(route('branding.update', absolute: false), [
            'name' => 'Logo Hospital',
            'default_preset' => 'clinical-indigo',
            'logo' => UploadedFile::fake()->image('logo.png', 64, 64),
        ])->assertRedirect(route('branding.edit'));

        $path = HospitalBranding::query()->firstOrFail()->logo_path;

        $this->actingAs($user)->put(route('branding.update', absolute: false), [
            'name' => 'Logo Hospital',
            'default_preset' => 'clinical-indigo',
            'remove_logo' => '1',
        ])->assertRedirect(route('branding.edit'));

        Storage::disk('public')->assertMissing($path);
        $this->assertNull(HospitalBranding::query()->firstOrFail()->logo_path);
    }

    public function test_inertia_shares_branding_defaults_and_permissions(): void
    {
        $user = $this->userWithRole('super admin');

        HospitalBranding::query()->create([
            'name' => 'St. Example Hospital',
            'default_preset' => 'warm-amber',
        ]);

        $pageData = $this->actingAs($user)
            ->get(route('dashboard', absolute: false))
            ->assertOk()
            ->viewData('page');

        $page = is_array($pageData)
            ? $pageData
            : (array) json_decode((string) $pageData, true);

        $this->assertSame('St. Example Hospital', $page['props']['name']);
        $this->assertSame('warm-amber', $page['props']['branding']['defaultPreset']);
        $this->assertNull($page['props']['branding']['allowedPresets']);
        $this->assertContains('settings.branding', $page['props']['permissions']);
    }
}
