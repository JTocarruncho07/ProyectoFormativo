# Sistema de Gestión - React + Node.js

Este proyecto es una versión moderna del sistema de gestión, construido con React para el frontend y Node.js para el backend.

## Requisitos Previos

- Node.js (v14 o superior)
- MySQL
- npm o yarn

## Configuración del Proyecto

1. Clonar el repositorio:
```bash
git clone <url-del-repositorio>
cd proyecto-formativo-react
```

2. Instalar dependencias del backend:
```bash
npm install
```

3. Instalar dependencias del frontend:
```bash
cd client
npm install
cd ..
```

4. Configurar variables de entorno:
   - Crear un archivo `.env` en la raíz del proyecto con las siguientes variables:
   ```
   PORT=5000
   DB_HOST=localhost
   DB_USER=root
   DB_PASSWORD=tu_contraseña
   DB_NAME=proyecto_formativo
   JWT_SECRET=tu_secreto_jwt
   ```

5. Configurar la base de datos:
   - Crear una base de datos MySQL llamada `proyecto_formativo`
   - Las tablas se crearán automáticamente al iniciar el servidor

## Ejecutar el Proyecto

1. Iniciar el servidor backend:
```bash
npm run dev
```

2. En otra terminal, iniciar el frontend:
```bash
npm run client
```

O ejecutar ambos simultáneamente:
```bash
npm run dev:full
```

El frontend estará disponible en `http://localhost:3000`
El backend estará disponible en `http://localhost:5000`

## Estructura del Proyecto

```
proyecto-formativo-react/
├── client/                 # Frontend React
│   ├── public/
│   └── src/
│       ├── components/     # Componentes React
│       ├── contexts/       # Contextos (Auth, etc.)
│       └── ...
├── routes/                 # Rutas del backend
├── models/                 # Modelos de la base de datos
├── middleware/            # Middleware (auth, etc.)
├── server.js              # Punto de entrada del backend
└── package.json
```

## Características

- Autenticación de usuarios
- Gestión de empleados
- Control de maquinaria
- Registro de gastos
- Registro de ventas
- Reportes y estadísticas
- Interfaz moderna y responsiva 