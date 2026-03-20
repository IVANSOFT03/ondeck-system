<?php
/**
 * Base path para enlaces internos (p. ej. '' en raíz del subdominio, o '/subcarpeta').
 * Las rutas a /assets siempre son absolutas desde la raíz del host: /assets/...
 */
if (!isset($base)) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $base = rtrim(str_replace('\\', '/', $scriptDir), '/');
    if ($base === '/' || $base === '.' || $base === '') {
        $base = '';
    }
}

/**
 * Origen público del sitio (Open Graph, canonical). Sin barra final.
 */
if (!defined('ONDECK_SITE_ORIGIN')) {
    define('ONDECK_SITE_ORIGIN', 'https://ondeck.nodo-digital.com');
}

/**
 * Ruta absoluta a /assets desde la raíz del dominio (p. ej. /assets o /sub/assets).
 * Nunca usar rutas relativas al directorio actual para /assets (solo rutas absolutas desde la raíz del sitio).
 */
if (!isset($assets_base)) {
    $assets_base = ($base === '' ? '' : $base) . '/assets';
    if ($assets_base === '' || ($assets_base[0] ?? '') !== '/') {
        $assets_base = '/' . ltrim((string) $assets_base, '/');
    }
}
