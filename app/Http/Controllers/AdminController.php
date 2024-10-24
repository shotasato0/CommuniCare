<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        // 管理者専用のダッシュボードを表示
        return inertia::render('AdminDashboard');
    }
}
