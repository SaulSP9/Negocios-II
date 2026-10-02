<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'usuario'], true);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['correo' => strtolower(trim((string) $this->correo)), 'nombre' => trim((string) $this->nombre)]);
    }

    public function rules(): array
    {
        return ['nombre' => 'required|string|min:2|max:120',
            'correo' => ['required', 'email', 'max:255', Rule::unique('clientes', 'correo')->ignore($this->route('cliente'))],
            'telefono' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+() .-]{7,25}$/'],
            'empresa' => 'nullable|string|max:150', 'estado' => 'required|in:activo,inactivo',
            'etapa_crm' => 'required|in:Prospecto,Activo,Frecuente,Inactivo'];
    }
}
