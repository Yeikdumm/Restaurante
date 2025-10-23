# Crafting Restaurante (PHP + MySQL)

Sistema base para heladería / ensaladería: pedidos por mesas y domicilios, inventario por gramos/unidades, recetas, caja, roles/permisos, dashboard.

## Requisitos
- PHP 8.1+ con PDO MySQL
- MySQL 5.7/8.0
- Servidor web apuntando a `public/` como webroot

## Instalación
1. Crea la base y tablas:
   ```sql
   SOURCE sql/install.sql;
   ```
2. Copia `.env.example` a `.env` y ajusta credenciales.
3. Sube todo al hosting y apunta el dominio/carpeta pública a `public/`.
4. Accede a `/login.php` con:
   - **Correo**: `admin@local`
   - **Contraseña**: `Admin123*`

> Cambia la contraseña del admin inmediatamente.

## Módulos (MVP)
- Dashboard (ventas del día, mesas ocupadas/disponibles, ingresos/egresos)
- Usuarios/Roles con permisos granulares
- Inventario (gramos y unidades) con costo promedio ponderado
- Productos & Recetas (BOM) que descuentan inventario
- Pedidos (mesa / domicilio), Pantalla de Cocina, Caja con ticket
- Reportes (exportar a CSV/Excel y PDF, próximamente)
- Configuración de empresa (NIT, IVA, logo)

## Notas de IVA
Precio público es **bruto** (con IVA). En la venta se descompone en neto + IVA usando la tasa configurada en empresa o del producto.

## Seguridad
- Passwords con `password_hash`/`password_verify` (bcrypt).
- CSRF básico en formularios.
- Roles/Permisos en sesión.

## Desarrollo
Estructura mínima MVC sin frameworks para máxima compatibilidad con hosting compartido.