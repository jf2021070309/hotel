<?php
$file = 'c:/xampp/htdocs/hotel/app/Models/RoomingModel.php';
$content = file_get_contents($file);

// 1. Actualizar SQL de Inserción en registrarStay
$search_insert_stay = '/INSERT INTO rooming_stays \(.*?tipo_comprobante, num_comprobante, ruc_factura, cobrador, procedencia/s';
$replace_insert_stay = 'INSERT INTO rooming_stays (
                operador, fecha_registro, fecha_checkout, hora_checkin, medio_reserva, 
                habitacion_id, tipo_hab_declarado, noches, pax_total, total_pago, 
                moneda_pago, monto_original, tc_aplicado, recargo_tarjeta, metodo_pago, 
                tipo_comprobante, num_comprobante, ruc_factura, razon_social, cobrador, procedencia';

$search_values_stay = '/:comprobante, :num_comp, :ruc, :cobrador, :procedencia/s';
$replace_values_stay = ':comprobante, :num_comp, :ruc, :razon_social, :cobrador, :procedencia';

$search_exec_stay = "/'ruc'           => \$data\['ruc'\],/";
$replace_exec_stay = "'ruc'           => \$data['ruc'],
                'razon_social'  => \$data['razon_social'] ?? '',";

$content = preg_replace($search_insert_stay, $replace_insert_stay, $content);
$content = preg_replace($search_values_stay, $replace_values_stay, $content);
$content = preg_replace($search_exec_stay, $replace_exec_stay, $content);

// 2. Actualizar SQL de Inserción de PAX en registrarStay
$search_insert_pax = '/INSERT INTO rooming_pax \(stay_id, nombre_completo, documento_tipo, documento_num, nacionalidad, ciudad, es_titular\)/';
$replace_insert_pax = 'INSERT INTO rooming_pax (stay_id, nombre_completo, documento_tipo, documento_num, nacionalidad, pais_origen, ciudad, celular, email, empresa, es_titular, es_corporativo)';

$search_values_pax = '/VALUES \(:stay_id, :nombre_completo, :documento_tipo, :documento_num, :nacionalidad, :ciudad, :es_titular\)/';
$replace_values_pax = 'VALUES (:stay_id, :nombre_completo, :documento_tipo, :documento_num, :nacionalidad, :pais_origen, :ciudad, :celular, :email, :empresa, :es_titular, :es_corporativo)';

$search_exec_pax = "/'es_titular'      => \$pax\['es_titular'\] \? 1 : 0/";
$replace_exec_pax = "'es_titular'      => \$pax['es_titular'] ? 1 : 0,
                    'pais_origen'     => \$pax['pais_origen'] ?? null,
                    'celular'         => \$pax['celular'] ?? null,
                    'email'           => \$pax['email'] ?? null,
                    'empresa'         => \$pax['empresa'] ?? null,
                    'es_corporativo'  => !empty(\$pax['es_corporativo']) ? 1 : 0";

$content = preg_replace($search_insert_pax, $replace_insert_pax, $content);
$content = preg_replace($search_values_pax, $replace_values_pax, $content);
$content = preg_replace($search_exec_pax, $replace_exec_pax, $content);


// 3. Actualizar actualizarStay (UPDATE stay)
$search_upd_stay = '/ruc_factura = :ruc, observaciones = :obs/s';
$replace_upd_stay = 'ruc_factura = :ruc, razon_social = :razon_social, observaciones = :obs';
$content = preg_replace($search_upd_stay, $replace_upd_stay, $content);

$search_upd_exec = "/'ruc'         => \$data\['ruc'\],/";
$replace_upd_exec = "'ruc'         => \$data['ruc'],
                'razon_social'  => \$data['razon_social'] ?? '',";
$content = preg_replace($search_upd_exec, $replace_upd_exec, $content);

// 4. Actualizar actualizarStay (REPLACE PAX)
$search_repl_pax = '/INSERT INTO rooming_pax \(stay_id, nombre_completo, documento_tipo, documento_num, es_titular\) VALUES \(\?, \?, \?, \?, \?\)/';
$replace_repl_pax = 'INSERT INTO rooming_pax (stay_id, nombre_completo, documento_tipo, documento_num, nacionalidad, pais_origen, ciudad, celular, email, empresa, es_titular, es_corporativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
$content = preg_replace($search_repl_pax, $replace_repl_pax, $content);

$search_repl_exec = '/\$stmtPax->execute\(\[\$id, \$p\[\'nombre_completo\'\], \$p\[\'documento_tipo\'\], \$p\[\'documento_num\'\], \$p\[\'es_titular\'\] \? 1 : 0\]\);/';
$replace_repl_exec = '$stmtPax->execute([
                    $id, 
                    $p[\'nombre_completo\'], 
                    $p[\'documento_tipo\'], 
                    $p[\'documento_num\'],
                    $p[\'nacionalidad\'] ?? \'\',
                    $p[\'pais_origen\'] ?? null,
                    $p[\'ciudad\'] ?? \'\',
                    $p[\'celular\'] ?? null,
                    $p[\'email\'] ?? null,
                    $p[\'empresa\'] ?? null,
                    $p[\'es_titular\'] ? 1 : 0,
                    !empty($p[\'es_corporativo\']) ? 1 : 0
                ]);';
$content = preg_replace($search_repl_exec, $replace_repl_exec, $content);

file_put_contents($file, $content);
echo "RoomingModel updated successfully with new fields.\n";
