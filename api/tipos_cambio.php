<?php
/**
 * api/tipos_cambio.php
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../auth/middleware.php';
require_once '../app/Models/TipoCambioModel.php';

// tipos_cambio es recurso de soporte: accesible si tiene rol cajera o módulo rooming
protegerPorRol('cajera', 'rooming');
$model = new TipoCambioModel($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Usamos el método estático que busca el más reciente (fecha <= hoy)
    $tc = TipoCambioModel::obtenerActual($pdo);
    
    // El frontend (Rooming) espera las llaves 'USD' y 'CLP'
    json_response(true, [
        'USD' => $tc['tc_usd'],
        'CLP' => $tc['tc_clp']
    ]);
}
