<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantLoginController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Auth/TenantLogin');
    }
}