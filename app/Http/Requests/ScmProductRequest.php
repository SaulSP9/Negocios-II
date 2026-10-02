<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScmProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return ['nombre' => 'required|string|min:2|max:120', 'descripcion' => 'required|string|min:3|max:3000',
            'categoria' => 'required|in:Hoodie,T-Shirt,Pants,Accessory', 'stock_actual' => $this->isMethod('POST') ? 'required|integer|between:0,1000000' : 'prohibited',
            'stock_minimo' => 'required|integer|between:0,999999', 'stock_objetivo' => 'required|integer|gt:stock_minimo|max:1000000',
            'proveedor_id' => ['nullable', Rule::requiredIf($this->estrategia_logistica === 'PUSH'), 'integer', 'exists:proveedores,id'],
            'costo_unitario' => 'required|numeric|between:0,1000000|decimal:0,2', 'precio_venta' => 'required|numeric|min:0.01|max:1000000|decimal:0,2',
            'estrategia_logistica' => 'required|in:PUSH,PULL', 'imagen' => 'required|url:http,https|max:2048',
            'color' => 'nullable|string|max:100', 'activo' => 'required|boolean', 'ubicacion' => 'required|string|max:120'];
    }
}
