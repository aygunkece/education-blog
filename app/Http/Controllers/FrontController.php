<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;

class FrontController extends Controller
{
    public function home()
    {
       $settings = Settings::first();
        return view("front.index",compact("settings"));
}
}
