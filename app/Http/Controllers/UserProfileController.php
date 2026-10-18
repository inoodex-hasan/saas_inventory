<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserProfileController extends Controller
{
    /**
     * Display the user's profile settings screen in Inertia.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('Profile/Show', [
            'status' => session('status'),
        ]);
    }
}
