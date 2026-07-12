<?php
require 'config/db.php';

$pdo->query("UPDATE habitaciones SET estado = 'libre' WHERE estado IN ('mantenimiento', 'sucio', 'limpieza', 'bloqueado')");
echo "Habitaciones actualizadas a libre.";
