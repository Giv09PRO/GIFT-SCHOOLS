<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
// Removed unused imports: Request, Auth

class HomeController extends Controller
{
        /**
     * Show the application public home page.
     *
     * @return Renderable
     */
    public function index(): Renderable // Use Renderable type hint
    {
        // Simply return the public home view
        return view('auth.login');
    }

}
