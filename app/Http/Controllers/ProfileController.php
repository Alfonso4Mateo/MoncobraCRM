<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the user profile.
     */
    public function show()
    {
        return view('profile.show');
    }

    /**
     * Show the form for editing the user profile.
     */
    public function edit()
    {
        return view('profile.edit');
    }

    /**
     * Update the user profile.
     */
    public function update(Request $request)
    {
       $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'apellido'      => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
            'dni_nie'       => ['nullable', 'string', 'max:20', 'unique:users,dni_nie,' . Auth::id()],
            'telefono'      => ['nullable', 'string', 'max:20'],
            'departamento'  => ['nullable', 'string', 'max:255'],
            'tipo_personal' => ['nullable', 'in:indefinido,temporal'],
            'descripcion'   => ['nullable', 'string', 'max:500'],
            'avatar'        => ['nullable', 'image', 'max:2048'],
        ]);

        $user = Auth::user();

        // Manejar upload de avatar
        if ($request->hasFile('avatar')) {
            // Eliminar avatar anterior si existe
            if ($user->avatar && \Storage::exists('public/' . $user->avatar)) {
                \Storage::delete('public/' . $user->avatar);
            }
            
            // Guardar nuevo avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        // Actualizar usuario
        $user->update($validated);

        return redirect()->route('profile.show')->with('success', 'Perfil actualizado correctamente');
    }

    /**
     * Update the user password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta']);
        }

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.show')->with('success', 'Contraseña actualizada correctamente');
    }
}