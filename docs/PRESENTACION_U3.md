# Presentación de 8 a 12 minutos

## Preparación

Ejecuta `php artisan hf:diagnostico`. Si necesitas datos ficticios, ejecuta `php artisan hf:demo-u3` y elige la contraseña de las cuentas DEMO. Abre el login como administrador.

## Recorrido

1. **Inicio ERP:** cinco KPIs, ventas por mes, productos vendidos, inventario crítico y tarjetas de integración.
2. **CRM:** muestra el cliente, su etapa y su historial. Es el mismo cliente que usará la orden.
3. **SCM:** busca `DEMO`, muestra stock, mínimo, proveedor y estrategias Push/Pull. Anota las existencias.
4. **Nueva orden:** selecciona cliente, agrega cantidades disponibles, avanza y regresa a productos para mostrar que conserva los datos.
5. **Procesar:** confirma una vez. Muestra folio, total, responsable y pasos registrados.
6. **Trazabilidad:** abre **Ver orden**, muestra movimientos e historial. Consulta SCM para verificar el stock.
7. **Push/Pull:** la orden demo inicial genera una reposición del Hoodie Push. Muéstrala en Pedidos SCM. Pull requiere pedido manual.
8. **Entrega:** completa una orden procesada. Explica que el inventario ya se descontó al procesarla.
9. **Reportes:** guarda un corte, consulta su fecha y descárgalo como CSV.
10. **Nivel ERP:** explica evidencias disponibles y cambio de nivel.
11. **Roles:** en ventanas privadas distintas, muestra ventas y logística; compara accesos y menús.
12. **Cancelación:** si hay tiempo, cancela una venta de prueba como administrador y muestra devolución y actualización del dashboard.

## Preguntas para la defensa

**¿Dónde se integra CRM?** La orden referencia al cliente existente. El alta desde el asistente registra un cliente real en CRM.

**¿Dónde se integra SCM?** Las partidas referencian `products`. El procesamiento utiliza el servicio SCM y registra movimientos con el identificador de la orden.

**¿Qué ocurre si falta stock?** Se rechaza y revierte la transacción completa.

**¿Qué ocurre al pulsar dos veces?** El botón se bloquea durante el envío y el backend usa una clave para conservar la misma operación.

**¿Por qué guardar precios por partida?** Para conservar el importe acordado aunque cambie el catálogo. El servidor calcula el total.

**¿Qué diferencia las órdenes?** ERP: venta al cliente. SCM: suministro o logística. Tienda: comercio electrónico; las ventas surtidas se incluyen en los reportes empresariales.

**¿Cómo se controla el acceso?** Sesión, hash de contraseña, CSRF, validaciones y permisos por módulo verificados en el backend. La asignación de roles corresponde al administrador.

**¿Qué beneficio aporta ERP?** Conecta cliente, catálogo, inventario, responsable, operación e indicadores, reduciendo registros manuales repetidos.

## Antes de exponer

Comprueba login, migraciones, diagnóstico, stock de los productos a utilizar, creación de una orden y consulta de su movimiento SCM. Mantén abierto el servidor o configura el dominio de Laragon hacia `public`.
