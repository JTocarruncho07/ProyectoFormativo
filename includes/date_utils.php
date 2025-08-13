<?php
/**
 * Función para formatear fechas al formato español
 * Ejemplo: "13 de agosto de 2025"
 */
function formatearFechaEspanol($fecha) {
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    
    $timestamp = strtotime($fecha);
    $dia = date('j', $timestamp);
    $mes = date('n', $timestamp);
    $año = date('Y', $timestamp);
    
    return $dia . ' de ' . $meses[$mes] . ' de ' . $año;
}

/**
 * Función para formatear mes y año al formato español
 * Ejemplo: "agosto 2025"
 */
function formatearMesAñoEspanol($fecha) {
    $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
    ];
    
    $timestamp = strtotime($fecha);
    $mes = date('n', $timestamp);
    $año = date('Y', $timestamp);
    
    return $meses[$mes] . ' ' . $año;
}
?>