<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('votmee:grant-role {email} {role}', function () {
    $role = (string) $this->argument('role');

    if (! in_array($role, ['user', 'moderator', 'admin'], true)) {
        $this->error('Role must be user, moderator, or admin.');

        return 1;
    }

    $user = User::query()->where('email', $this->argument('email'))->firstOrFail();

    DB::transaction(function () use ($user, $role): void {
        $user->forceFill(['role' => $role])->save();
        DB::table('audit_logs')->insert([
            'actor_id' => null,
            'action' => 'user.role.grant',
            'target_type' => 'user',
            'target_id' => (string) $user->id,
            'details' => json_encode(['role' => $role, 'source' => 'console'], JSON_THROW_ON_ERROR),
            'created_at' => now('UTC'),
        ]);
    });

    $this->info("Role {$role} assigned to {$user->email}.");

    return 0;
})->purpose('Assign a Votmee role to an existing account');
