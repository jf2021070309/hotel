<?php
$file = 'c:/xampp/htdocs/hotel/app/Views/rooming/index.js';
$content = file_get_contents($file);

// 1. Inicializar excluir: false en cargarReportePax
$search_cargar = '/reportePax\.filas = res\.data\.data \|\| \[\];/';
$replace_cargar = 'reportePax.filas = (res.data.data || []).map(f => ({ ...f, excluir: false }));';
$content = preg_replace($search_cargar, $replace_cargar, $content);

// 2. Modificar exportarReportePax para filtrar rows
// Necesito identificar el ID de la estadía para filtrar acompañantes también si el titular está excluido
// Asumo que existe f.id (del stay) o similar. Mirando la tabla original, f.es_titular se usa mucho.

$search_export_filter = '/reportePax\.filas\.forEach\(f => \{/';
$replace_export_filter = '// Identificar los IDs de estadías a excluir (donde el titular marcó el checkbox)
      const staysExcluded = reportePax.filas.filter(f => f.es_titular && f.excluir).map(f => f.stay_id || f.id);
      
      reportePax.filas.forEach(f => {
        // Si la fila actual pertenece a un stay excluido, no la procesamos
        const currentStayId = f.stay_id || f.id;
        if (staysExcluded.includes(currentStayId)) return;
';

$content = preg_replace($search_export_filter, $replace_export_filter, $content);

file_put_contents($file, $content);
echo "Exclusion logic added to report export.\n";
