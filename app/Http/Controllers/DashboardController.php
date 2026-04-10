<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class DashboardController extends Controller
{

    public function index()
    {
        return view('comprador.dashboard');
    }

    public function adminDashboard()
    {
        $totalUsuarios = User::count();

        return view('admin.dashboard', compact('totalUsuarios'));
    }

}