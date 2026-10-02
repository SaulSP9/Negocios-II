<?php

return [
    'required' => 'El campo :attribute es obligatorio.', 'email' => 'El campo :attribute debe ser un correo válido.',
    'unique' => 'El valor de :attribute ya está registrado.', 'exists' => 'El registro seleccionado en :attribute no existe.',
    'in' => 'El valor de :attribute no está permitido.', 'integer' => 'El campo :attribute debe ser un entero.',
    'numeric' => 'El campo :attribute debe ser numérico.', 'date' => 'El campo :attribute debe ser una fecha válida.',
    'before_or_equal' => 'El campo :attribute no debe superar :date.', 'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'min' => ['string' => 'El campo :attribute necesita al menos :min caracteres.', 'numeric' => 'El campo :attribute debe ser mayor o igual a :min.', 'array' => 'El campo :attribute necesita al menos :min elemento(s).'],
    'max' => ['string' => 'El campo :attribute admite hasta :max caracteres.', 'numeric' => 'El campo :attribute debe ser menor o igual a :max.', 'array' => 'El campo :attribute admite hasta :max elementos.'],
    'gt' => ['numeric' => 'El campo :attribute debe ser mayor que :value.'],
    'gte' => ['numeric' => 'El campo :attribute debe ser mayor o igual a :value.'],
    'prohibited' => 'El campo :attribute no se edita aquí. Registra un movimiento de inventario.',
    'array' => 'El campo :attribute debe contener una lista válida.',
    'between' => ['numeric' => 'El campo :attribute debe estar entre :min y :max.'],
    'regex' => 'El formato de :attribute no es válido.', 'url' => 'El campo :attribute debe ser una URL HTTP o HTTPS válida.',
    'decimal' => 'El campo :attribute debe tener entre :min y :max decimales.', 'uuid' => 'El campo :attribute debe ser un UUID válido.',
    'distinct' => 'El campo :attribute contiene elementos repetidos.', 'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'attributes' => ['nombre' => 'nombre', 'correo' => 'correo', 'telefono' => 'teléfono', 'etapa_crm' => 'etapa CRM', 'password' => 'contraseña', 'name' => 'nombre', 'email' => 'correo', 'description' => 'descripción', 'price' => 'precio', 'stock' => 'existencias', 'zip' => 'código postal', 'address' => 'dirección', 'city' => 'ciudad', 'items' => 'productos', 'descripcion' => 'descripción', 'fecha' => 'fecha', 'puntuacion' => 'puntuación', 'amount' => 'oferta', 'stock_actual' => 'stock actual', 'stock_minimo' => 'stock mínimo', 'stock_objetivo' => 'stock objetivo', 'proveedor_id' => 'proveedor', 'costo_unitario' => 'costo unitario', 'precio_venta' => 'precio de venta', 'estrategia_logistica' => 'estrategia logística', 'ubicacion' => 'ubicación', 'cantidad' => 'cantidad', 'nivel_scm' => 'nivel SCM'],
];
