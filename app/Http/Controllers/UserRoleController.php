<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserRoleController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate(['usuario_id' => 'required|integer|exists:users,id', 'role' => ['required', Rule::in(Permissions::ROLES)]]);
        abort_if((int) $data['usuario_id'] === $request->user()->id && $data['role'] !== 'admin', 409, 'Conserva tu cuenta administradora. Cambia los permisos de otra cuenta.');

        return DB::transaction(function () use ($data) {
            $admins = User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
            $user = User::whereKey($data['usuario_id'])->lockForUpdate()->firstOrFail();
            abort_if($user->role === 'admin' && $data['role'] !== 'admin' && $admins->count() <= 1, 409, 'El sistema necesita al menos un administrador.');
            $user->role = $data['role'];
            $user->save();

            return $user;
        }, 3);
    }
}
