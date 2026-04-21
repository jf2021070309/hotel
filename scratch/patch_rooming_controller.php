<?php
$file = 'c:/xampp/htdocs/hotel/app/Controllers/RoomingController.php';
$content = file_get_contents($file);

$search_map = "/'ruc'          => \$stayData\['ruc_factura'\] \?\? '',/";
$replace_map = "'ruc'          => \$stayData['ruc_factura'] ?? '',
            'razon_social' => \$stayData['razon_social'] ?? '',";

$content = preg_replace($search_map, $replace_map, $content);

file_put_contents($file, $content);
echo "RoomingController updated with razon_social mapping.\n";
