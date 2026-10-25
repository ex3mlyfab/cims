<?php

namespace Tests\Feature\Public;

use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_lists_public_ward_displays_in_code_order(): void
    {
        Ward::query()->create([
            'code' => 'W-02',
            'name' => 'Recovery',
            'capacity' => 12,
        ]);
        Ward::query()->create([
            'code' => 'W-01',
            'name' => 'Emergency',
            'capacity' => 8,
        ]);

        $this->get(route('home', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->has('wards', 2)
                ->where('wards.0.code', 'W-01')
                ->where('wards.0.name', 'Emergency')
                ->where('wards.0.displayUrl', '/display/'.Ward::query()->where('code', 'W-01')->value('id'))
                ->where('wards.1.code', 'W-02'));
    }

    public function test_homepage_has_a_valid_empty_state_when_no_wards_exist(): void
    {
        $this->get(route('home', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('welcome')
                ->has('wards', 0));
    }
}
