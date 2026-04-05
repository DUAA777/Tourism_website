<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Restaurant;
use App\Models\Hotel;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_admins' => User::where('is_admin', true)->count(),
            'total_restaurants' => Restaurant::count(),
            'total_hotels' => Hotel::count(),
            'recent_users' => User::latest()->take(5)->get(),
            'recent_restaurants' => Restaurant::latest()->take(5)->get(),
            'recent_hotels' => Hotel::latest()->take(5)->get(),
        ];

        return view('admin.dashboard', $stats);
    }
}