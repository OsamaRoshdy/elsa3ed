<?php

use App\Models\SyncRun;
use App\Models\User;
use App\Services\DaftraException;
use App\Services\DaftraService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

Artisan::command('daftra:sync', function (DaftraService $daftra) {
    $this->info('Synchronizing with Daftra...');

    try {
        $run = $daftra->syncAll('scheduled');
    } catch (DaftraException $exception) {
        $this->error($exception->getMessage());

        return 1;
    }

    if ($run->status !== SyncRun::STATUS_SUCCESS) {
        $this->error('Sync failed: '.$run->error);

        return 1;
    }

    $this->table(['Products', 'Clients', 'Invoices', 'Payments'], [[
        $run->products_count, $run->clients_count, $run->invoices_count, $run->payments_count,
    ]]);

    return 0;
})->purpose('Synchronize products, stock, clients, invoices and payments from Daftra');

Artisan::command('portal:create-admin', function () {
    $data = [
        'name' => text('Name', required: true),
        'email' => text('Email', required: true),
        'password' => password('Password (min 8 characters)', required: true),
    ];

    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email'],
        'password' => ['required', 'min:8'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    User::create($data + ['role' => User::ROLE_ADMIN]);
    $this->info("Admin {$data['email']} created.");

    return 0;
})->purpose('Create a portal admin user');

/*
 * Daftra has no webhook for stock movements or payments, so a periodic sync keeps
 * stock and balances accurate. Requires the Laravel scheduler cron entry on the server.
 */
Schedule::command('daftra:sync')->everyFifteenMinutes()->withoutOverlapping();
