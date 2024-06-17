<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CacheTestController extends Controller
{
    public function put()
    {
        Cache::put('tenant_test', 'test_value', 60);
        return response()->json(['message' => 'Cache put successfully']);
    }

    public function get()
    {
        $value = Cache::get('tenant_test');
        return response()->json(['value' => $value]);
    }
}