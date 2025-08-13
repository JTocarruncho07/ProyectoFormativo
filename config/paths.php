<?php
// Configuración de rutas del proyecto
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('MODULES_PATH', ROOT_PATH . '/modules');
define('TOOLS_PATH', ROOT_PATH . '/tools');
define('DATABASE_PATH', ROOT_PATH . '/database');

// Rutas relativas desde el directorio raíz del proyecto
define('ROOT_URL', '/ProyectoFormativo');
define('ASSETS_URL', ROOT_URL . '/assets');
define('MODULES_URL', ROOT_URL . '/modules');
define('TOOLS_URL', ROOT_URL . '/tools');

// Función para incluir archivos de configuración
function includeConfig($file) {
    return include CONFIG_PATH . '/' . $file;
}

// Función para incluir templates
function includeTemplate($file) {
    include TEMPLATES_PATH . '/' . $file;
}

// Función para obtener URL de assets
function assetUrl($path) {
    return ASSETS_URL . '/' . $path;
}

// Función para obtener URL de módulos
function moduleUrl($path) {
    return MODULES_URL . '/' . $path;
}
?>
