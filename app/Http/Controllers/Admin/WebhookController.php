<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use Illuminate\View\View;

class WebhookController extends Controller
{
    public function __invoke(): View
    {
        $secret = (string) config('services.daftra.webhook_secret');

        return view('admin.webhooks', [
            'logs' => WebhookLog::latest('id')->paginate(25),
            'webhookUrl' => route('webhooks.daftra').($secret !== '' ? '?token='.$secret : ''),
            'hasSecret' => $secret !== '',
        ]);
    }
}
