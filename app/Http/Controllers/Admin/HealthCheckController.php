<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HealthCheckService;
use Inertia\Inertia;

class HealthCheckController extends Controller
{
    public function index(HealthCheckService $service)
    {
        return Inertia::render('Admin/HealthCheck', [
            'checks' => $service->run(),
            'ranAt'  => now()->toIso8601String(),
        ]);
    }
}
