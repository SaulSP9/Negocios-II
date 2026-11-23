# Validación de HF Studios. · Unidad 4

## Correspondencia con la actividad

| Bloque | Validación | Evidencia |
| --- | --- | --- |
| 1 · CRM | Clientes, etapas, historial, roles y métricas. Apertura y respuesta de tickets registradas en el historial. | `CrmTest`, `EcommerceU4Test`, `/gestion/clientes`. |
| 2 · SCM | Inventario común, movimientos, reglas PUSH/PULL, reposición bajo umbral y submenú de reportes. | `ScmTest`, `EcommerceU4Test`, `/gestion/inventario`. |
| 3 · ERP | Orden vinculada al cliente CRM y a la operación de salida. Cancelación sincronizada con la tienda. | `ErpTest`, `EcommerceU4Test`, `/gestion/operaciones`. |
| 4.1.1 · Catálogo | Filtros por familia y nombre, ordenamiento, favoritos locales y ficha por ID con galería, talla y stock. | `EcommerceU4Test` y `/catalogo`. |
| 4.1.2 · Pagos | Cálculo en servidor, validación de stock, pago único, entrega, pedido, salida de inventario, checkout y recibo en páginas propias, y confirmación por correo. | `EcommerceU4Test`, `POST /pagos/procesar`. |
| 4.1.3 · Carrito | Persistencia, traspaso del carrito de invitado, incremento, decremento, borrado y aislamiento por cliente. | `EcommerceU4Test`, panel Carrito. |
| 4.1.4 · Comercialización | Ofertas vigentes por fechas, categoría o producto, mejor descuento, precios visibles y cuenta regresiva. | `EcommerceU4Test`, carrusel y Campaña. |
| 4.1.5 · Impuestos | IVA 16% al instalar, configuración restringida y desglose. | `EcommerceU4Test`, panel Impuestos. |
| 4.1.6 · Atención | Creación por categoría, filtros, historial de actualizaciones, respuesta por personal autorizado y seguimiento en CRM. | `EcommerceU4Test`, Ayuda y Atención. |
| 4.2 · Base de datos | Llaves foráneas, restricciones y conservación de tablas de las etapas 1–3. | Migración `2026_11_22_000001_add_ecommerce_u4.php`. |
| 4.3 · Registro | Acceso y registro en vistas independientes, sesión regenerada, recuperación de contraseña y navegación exclusiva por perfil, monedero, pedidos o tickets. | `EcommerceU4Test`, Mi cuenta. |
| Agregado · Monedero | Recarga simulada, historial de cargos y abonos, ajustes y exportación CSV. | `EcommerceU4Test`, Mi monedero y Gestión empresarial. |
| 5 · Cierre | Procedimientos de instalación, comandos de validación y evidencia de pruebas. | `TIENDA_U4.md`, este documento y `RESULTADOS_PRUEBAS_U4.txt`. |

## Validación reproducible

```sh
php artisan migrate:fresh --seed
php artisan hf:verificar-imagenes
php artisan test
npm ci
npm run build
npm run test:ui
```

Los comandos de reinicio se ejecutan sobre una base de demostración sin datos que conservar. Las extensiones de PHP para PHPUnit incluyen DOM, XML, XMLWriter, Mbstring, Tokenizer y los controladores PDO del motor elegido. El verificador de imágenes inspecciona archivos de catálogo, categorías, sección, carrusel y subasta con `file_exists()` y `getimagesize()`.

## Secuencia para la defensa

1. Entra como Cliente Demo, filtra productos y muestra el precio anterior y el descontado.
2. Agrega 3 Hoodie Esencial para alcanzar el umbral PUSH. Recorre entrega, pago y recibo.
3. Abre el pedido generado, revisa el historial del monedero, registra un ticket y cierra la sesión.
4. Entra como Administración. Abre **Gestión empresarial → Tienda y atención**, revisa las pestañas y responde el ticket.
5. En CRM muestra el cliente y sus dos interacciones. En SCM muestra venta y reposición PUSH. En ERP abre la orden asociada a la compra.
6. Explica la validación del stock por prenda, el redondeo del impuesto y el UUID que evita un segundo cobro.

El PDF deja en blanco el apartado de entregables de Etapa 5. La evidencia aquí corresponde a los tres documentos solicitados. El PDF asigna 60% al proyecto grupal, 25% a la evidencia individual y 15% a la defensa oral.
