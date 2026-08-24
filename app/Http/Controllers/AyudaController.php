<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AyudaController extends Controller
{
    /**
     * Muestra el panel principal de ayuda y documentación.
     */
    public function index()
    {
        return view('ayuda.index');
    }
}