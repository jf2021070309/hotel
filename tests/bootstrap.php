<?php
/**
 * tests/bootstrap.php
 * Configuración inicial para las pruebas automatizadas.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Definir constantes o variables globales necesarias para el entorno de test
if (!defined('APP_ENV')) define('APP_ENV', 'testing');

// Sincronizar zona horaria
date_default_timezone_set('America/Lima');

echo "--- Bootstrap de Pruebas Cargado ---\n";
