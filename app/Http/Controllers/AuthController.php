<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function session(Request $request)
    {
        return ['user' => $request->user(), 'csrf_token' => csrf_token()];
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string|max:128']);
        $credentials['email'] = strtolower(trim($credentials['email']));
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'Correo o contraseña incorrectos.']);
        }
        $request->session()->regenerate();

        return $this->session($request);
    }

    public function register(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->email))]);
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'email' => 'required|email|max:255|unique:users,email', 'password' => 'required|string|min:12|max:128']);
        $user = DB::transaction(function () use ($data) {
            $user = User::create($data);
            Cliente::firstOrCreate(['correo' => $data['email']], ['nombre' => $data['name']]);

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json($this->session($request), 201);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->session($request);
    }
}
