<?php

namespace App\Http\Controllers;

class RegistroCompradorController extends Controller
{
    public function index()
    {
        return view('comprador.registros.index');
    }
}