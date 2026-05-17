<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class CharacterAdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Characters/Index');
    }
}
