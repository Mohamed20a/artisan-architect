<?php

namespace Workbench\App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BadController
{
    public function index()
    {
        // ❌ Anti-pattern: Direct DB call
        $users = DB::table('users')->get();
        return response()->json($users);
    }

    public function store(Request $request)
    {
        // ❌ Anti-pattern: Inline validation instead of FormRequest
        $request->validate(['name' => 'required']);
    }

    // ❌ Anti-pattern: Too many methods (I'll just add dummy ones to trigger the rule if max is low)
    public function m1() {}
    public function m2() {}
    public function m3() {}
    public function m4() {}
    public function m5() {}
    public function m6() {}
    public function m7() {}
    public function m8() {}
    public function m9() {}
    public function m10() {}
    public function m11() {}
}
