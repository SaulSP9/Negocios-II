<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InteraccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'usuario'], true);
    }

    public function rules(): array
    {
        return ['cliente_id' => 'required|integer|exists:clientes,id', 'tipo' => 'required|in:llamada,correo,reunión',
            'descripcion' => 'required|string|min:3|max:3000', 'fecha' => 'required|date|before_or_equal:now'];
    }
}
