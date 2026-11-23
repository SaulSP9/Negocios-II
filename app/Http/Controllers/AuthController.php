<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Monedero;
use App\Models\Order;
use App\Models\User;
use App\Services\CommerceCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function session(Request $request)
    {
        return ['user' => $request->user()?->append(['permissions', 'role_label', 'home']), 'csrf_token' => csrf_token()];
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
        $data = $request->validate(['name' => 'required|string|min:2|max:120', 'email' => 'required|email|max:255|unique:users,email', 'password' => ['required', 'string', 'min:12', 'max:72', ...($request->has('password_confirmation') ? ['confirmed'] : [])]]);
        $user = DB::transaction(function () use ($data) {
            $user = User::create($data);
            $client = Cliente::firstOrCreate(['correo' => $data['email']], ['nombre' => $data['name']]);
            abort_if(User::where('cliente_id', $client->id)->exists(), 409, 'Este perfil ya tiene una cuenta.');
            $user->forceFill(['cliente_id' => $client->id])->save();

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json($this->session($request), 201);
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        Password::sendResetLink(['email' => strtolower(trim($data['email']))]);

        return ['message' => 'Si la cuenta existe, recibirá un enlace para recuperar el acceso.'];
    }

    public function showReset(Request $request, string $token)
    {
        $email = $request->query('email');

        return view('reset-password', ['token' => $token, 'email' => is_string($email) ? $email : '']);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'token' => 'required|string',
            'password' => 'required|string|min:12|max:72|confirmed', 'password_confirmation' => 'required|string']);
        $data['email'] = strtolower(trim($data['email']));
        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill(['password' => $password])->setRememberToken(Str::random(60));
            $user->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'El enlace venció o ya se usó. Solicita uno nuevo.'])->onlyInput('email');
        }

        return redirect('/?vista=cuenta')->with('password_reset', 'Contraseña actualizada. Ya puedes iniciar sesión.');
    }

    public function demo(Request $request)
    {
        abort_unless(app()->environment('local', 'testing') && config('commerce.demo'), 404);
        $user = User::where('email', config('commerce.demo_email'))->where('role', 'cliente')->whereNotNull('cliente_id')->firstOrFail();
        Auth::login($user);
        $request->session()->regenerate();

        return $this->session($request);
    }

    public function profile(Request $request, CommerceCart $carts)
    {
        $user = $request->user();
        $client = $carts->client($user);

        return [
            'cliente' => $client->only(['id', 'nombre', 'telefono', 'direccion', 'ciudad', 'codigo_postal']),
            'email' => $user->email,
            'saldo_cents' => (int) Monedero::where('cliente_id', $client->id)->value('saldo_cents'),
            'pedidos' => Order::with(['items', 'user', 'pago', 'cargoEnMonedero'])->where('user_id', $user->id)->latest('id')->paginate(20)->through(fn ($order) => $order->presentation()),
        ];
    }

    public function updateProfile(Request $request, CommerceCart $carts)
    {
        $data = $request->validate([
            'nombre' => 'required|string|min:2|max:120', 'telefono' => 'nullable|string|max:25',
            'direccion' => 'nullable|string|max:255', 'ciudad' => 'nullable|string|max:120', 'codigo_postal' => 'nullable|digits:5',
        ]);
        $user = $request->user();
        $client = $carts->client($user);
        DB::transaction(function () use ($data, $user, $client) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $client = Cliente::lockForUpdate()->findOrFail($client->id);
            $client->forceFill($data)->save();
            $user->update(['name' => $data['nombre']]);
        }, 3);

        return $this->profile($request, $carts);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->session($request);
    }
}
