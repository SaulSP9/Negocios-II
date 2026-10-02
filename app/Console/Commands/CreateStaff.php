<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateStaff extends Command
{
    protected $signature = 'hf:crear-usuario {--role=admin} {--name=} {--email=}';

    protected $description = 'Crea una cuenta del equipo; solicita la contraseña sin mostrarla.';

    public function handle(): int
    {
        $role = $this->option('role');
        $name = $this->option('name') ?: $this->ask('Nombre');
        $email = strtolower(trim($this->option('email') ?: $this->ask('Correo')));
        $password = $this->secret('Contraseña (mínimo 12 caracteres)');
        $validator = Validator::make(compact('role', 'name', 'email', 'password'), ['role' => 'required|in:admin,usuario', 'name' => 'required|string|min:2|max:120', 'email' => 'required|email|max:255|unique:users,email', 'password' => 'required|string|min:12|max:128']);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

return self::FAILURE;
        }
        $user = new User(compact('name', 'email', 'password'));
        $user->role = $role;
        $user->save();
        $this->info('Cuenta creada.');

        return self::SUCCESS;
    }
}
