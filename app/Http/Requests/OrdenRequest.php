<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('erp.write') ?? false;
    }

    public function rules(): array
    {
        return ['cliente_id' => ['required', 'integer', Rule::exists('clientes', 'id')->where('estado', 'activo')],
            'request_key' => 'required|uuid', 'notas' => 'nullable|string|max:500',
            'productos' => 'required|array|min:1|max:50',
            'productos.*' => 'required|array:producto_id,cantidad,precio_unitario_cents',
            'productos.*.producto_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'productos.*.cantidad' => 'required|integer|between:1,1000000',
            'productos.*.precio_unitario_cents' => 'nullable|integer|between:1,100000000'];
    }

    public function messages(): array
    {
        return ['cliente_id.exists' => 'Selecciona un cliente activo del CRM.', 'productos.*.producto_id.distinct' => 'Agrega cada producto una sola vez y ajusta su cantidad.', 'productos.*.producto_id.exists' => 'Uno de los productos fue archivado o ya no existe.'];
    }
}
