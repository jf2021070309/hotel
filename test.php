<?php

echo "<h1>PHP OK</h1>";

echo "<h2>Variables Railway:</h2>";

echo "MYSQLHOST: " . getenv('MYSQLHOST') . "<br>";
echo "MYSQLPORT: " . getenv('MYSQLPORT') . "<br>";
echo "MYSQLDATABASE: " . getenv('MYSQLDATABASE') . "<br>";
echo "MYSQLUSER: " . getenv('MYSQLUSER') . "<br>";

echo "<hr>";

$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT');
$db   = getenv('MYSQLDATABASE');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');

$conn = new mysqli($host, $user, $pass, $db, (int)$port);

if ($conn->connect_error) {
    echo "<h2 style='color:red'>ERROR MYSQL:</h2>";
    echo $conn->connect_error;
} else {
    echo "<h2 style='color:green'>MYSQL CONECTADO OK</h2>";
}