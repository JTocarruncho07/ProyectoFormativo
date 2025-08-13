# 🏗️ Nueva Estructura del Proyecto - Mina Recebo

## 📁 Estructura Propuesta

```
ProyectoFormativo/
├── 📁 assets/
│   ├── 📁 css/
│   │   └── style.css
│   ├── 📁 js/
│   │   └── main.js
│   └── 📁 img/
│       └── user-icon.png
├── 📁 config/
│   ├── config.php
│   └── database.php
├── 📁 database/
│   ├── migrations/
│   │   └── migracion.sql
│   └── seeds/
│       └── datos_prueba.sql
├── 📁 includes/
│   ├── auth.php
│   ├── database.php
│   └── functions.php
├── 📁 modules/
│   ├── 📁 auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── forgot-password.php
│   │   ├── reset-password.php
│   │   └── update-password.php
│   ├── 📁 machines/
│   │   ├── control_maquinas.php
│   │   ├── registrar_maquina.php
│   │   ├── editar_maquina.php
│   │   ├── eliminar_maquina.php
│   │   ├── gastos_maquinas.php
│   │   ├── registrar_gasto_maquina.php
│   │   ├── procesar_gasto_maquina.php
│   │   ├── historial_maquina.php
│   │   ├── guardar_horas.php
│   │   └── historial_gastos.php
│   ├── 📁 employees/
│   │   ├── empleados.php
│   │   ├── crear_empleado.php
│   │   ├── editar_empleado.php
│   │   └── eliminar_empleado.php
│   ├── 📁 finances/
│   │   ├── nueva_venta.php
│   │   ├── nuevo_gasto.php
│   │   ├── procesar_venta.php
│   │   ├── procesar_gasto.php
│   │   └── procesar_pago.php
│   └── 📁 reports/
│       ├── reporte.php
│       └── reporte_anual.php
├── 📁 templates/
│   ├── header.php
│   ├── footer.php
│   ├── nav.php
│   └── alerts.php
├── 📁 vendor/
│   └── phpmailer/
├── 📁 tools/
│   ├── verificar_instalacion.php
│   ├── insertar_datos_prueba.php
│   └── instalar.php
├── index.php
├── README.md
└── .htaccess
```

## 🔄 Archivos a Mover

### 📁 assets/
- `css/style.css` → `assets/css/style.css`
- `img/user-icon.png` → `assets/img/user-icon.png`

### 📁 config/
- `config.php` → `config/config.php`

### 📁 database/
- `data/migracion.sql` → `database/migrations/migracion.sql`

### 📁 modules/auth/
- `login.php` → `modules/auth/login.php`
- `logout.php` → `modules/auth/logout.php`
- `procesar_login.php` → `modules/auth/procesar_login.php`
- `RESTABLECERCONTRASEÑA/` → `modules/auth/`

### 📁 modules/machines/
- `control_maquinas.php` → `modules/machines/control_maquinas.php`
- `registrar_maquina.php` → `modules/machines/registrar_maquina.php`
- `editar_maquina.php` → `modules/machines/editar_maquina.php`
- `eliminar_maquina.php` → `modules/machines/eliminar_maquina.php`
- `gastos_maquinas.php` → `modules/machines/gastos_maquinas.php`
- `registrar_gasto_maquina.php` → `modules/machines/registrar_gasto_maquina.php`
- `procesar_gasto_maquina.php` → `modules/machines/procesar_gasto_maquina.php`
- `historial_maquina.php` → `modules/machines/historial_maquina.php`
- `guardar_horas.php` → `modules/machines/guardar_horas.php`
- `historial_gastos.php` → `modules/machines/historial_gastos.php`

### 📁 modules/employees/
- `empleados.php` → `modules/employees/empleados.php`
- `crear_empleado.php` → `modules/employees/crear_empleado.php`
- `editar_empleado.php` → `modules/employees/editar_empleado.php`
- `eliminar_empleado.php` → `modules/employees/eliminar_empleado.php`

### 📁 modules/finances/
- `nueva_venta.php` → `modules/finances/nueva_venta.php`
- `nuevo_gasto.php` → `modules/finances/nuevo_gasto.php`
- `procesar_venta.php` → `modules/finances/procesar_venta.php`
- `procesar_gasto.php` → `modules/finances/procesar_gasto.php`
- `procesar_pago.php` → `modules/finances/procesar_pago.php`

### 📁 modules/reports/
- `reporte.php` → `modules/reports/reporte.php`
- `reporte_anual.php` → `modules/reports/reporte_anual.php`

### 📁 tools/
- `verificar_instalacion.php` → `tools/verificar_instalacion.php`
- `insertar_datos_prueba.php` → `tools/insertar_datos_prueba.php`
- `instalar.php` → `tools/instalar.php`

## 🔧 Archivos a Crear

### 📁 includes/
- `includes/auth.php` - Funciones de autenticación
- `includes/database.php` - Conexión a base de datos
- `includes/functions.php` - Funciones generales

### 📁 assets/js/
- `assets/js/main.js` - JavaScript principal

### 📁 templates/
- `templates/nav.php` - Navegación
- `templates/alerts.php` - Alertas y mensajes

### 📁 config/
- `config/database.php` - Configuración específica de BD

## 🎯 Beneficios de esta Estructura

1. **Organización Modular**: Cada funcionalidad en su carpeta
2. **Mantenibilidad**: Fácil de mantener y actualizar
3. **Escalabilidad**: Fácil agregar nuevas funcionalidades
4. **Seguridad**: Separación clara de archivos sensibles
5. **Profesional**: Estructura estándar de la industria
