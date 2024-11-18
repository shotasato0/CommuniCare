<?php

namespace App\Http\Controllers;

class GuestTenantController extends Controller
{
    public function redirectToGuestTenant()
    {
        // ゲスト用テナントのサブドメインにリダイレクト
        return redirect('http://guestdemo.localhost/home');
    }
}
