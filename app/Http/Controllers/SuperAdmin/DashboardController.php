<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('super-admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'roles' => Role::count(),
            ],
        ]);
    }
}
