<?php
/**
 * ajax/bootstrap.php
 * Define BASE_PATH si no fue definida por public/index.php.
 * Incluir al inicio de cada endpoint AJAX.
 */
if (!defined('BASE_PATH')) {
    $basePath = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
    define('BASE_PATH', $basePath . DIRECTORY_SEPARATOR);
}
