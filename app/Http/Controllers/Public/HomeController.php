<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('welcome', [
            'wards' => Ward::query()
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(static fn (Ward $ward): array => [
                    'id' => $ward->id,
                    'code' => $ward->code,
                    'name' => $ward->name,
                    'displayUrl' => route('display.ward', ['ward' => $ward->id], false),
                ])
                ->values(),
        ]);
    }
}
