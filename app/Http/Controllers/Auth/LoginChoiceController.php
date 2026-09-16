<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LoginChoiceController extends Controller
{
    public function __invoke(): View
    {
        return view('auth.choice');
    }
}
