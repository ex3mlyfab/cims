<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = Illuminate\Support\Facades\DB::table('sessions')->orderByDesc('last_activity')->limit(6)->get();
foreach ($rows as $row) {
    $user = App\Models\User::find($row->user_id);
    echo $row->id.' | '.date('H:i:s', $row->last_activity).' | user='.($user?->username ?? '-').' | '.substr((string) $row->user_agent, 0, 40).PHP_EOL;
}
