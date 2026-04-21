<?php
$file = 'c:/xampp/htdocs/hotel/app/Views/rooming/index.php';
$content = file_get_contents($file);

// 1. Fix Modal Detalle Header (Remove the rogue tbody and rows injected inside the modal)
$modal_search = '/<div class="d-flex justify-content-between align-items-center">\s+<div>\s+<h4 class="mb-0 fw-bold">Habitación #\{\{ selectedStay\.hab_numero \}\}<\/h4>\s+<p class="mb-0 opacity-75 small text-uppercase fw-bold">\{\{ selectedStay\.tipo_hab_declarado \}\}<\/p>\s+<\/div>\s+<tbody>.*?<\/div>\s+<\/div>/s';
$modal_replace = '<div class="d-flex justify-content-between align-items-center">
                <div>
                  <h4 class="mb-0 fw-bold">Habitación #{{ selectedStay.hab_numero }}</h4>
                  <p class="mb-0 opacity-75 small text-uppercase fw-bold">{{ selectedStay.tipo_hab_declarado }}</p>
                </div>
                <span class="badge bg-white text-primary px-3 fs-6 shadow-sm">{{ selectedStay.estado.toUpperCase() }}</span>
              </div>';

$content = preg_replace($modal_search, $modal_replace, $content);

// 2. Fix the Table Body (Reconstruct the damaged tbody and first few cells)
// We need to find the point where it's broken.
// Based on the last view, line 99-106 was: </tr> </thead> <tbody> <tr v-if="loading"> ... <tr v-else> ... <td> Habitacion ... </td>
// But the tool replaced it with garbage.

// Let's attempt a broad reach fix for the table section.
$table_search = '/<thead>\s+<tr>\s+<th class="ps-4".*?<\/tr>\s+<\/thead>.*?<div class="text-dark">/s';
// This is risky.

// Better: Just target the broken transition from the header to the body.
$content = preg_replace('/<\/tr>\s+<\/thead>\s+<tr/s', '</tr>
            </thead>
            <tbody>
              <tr', $content);

// And fix the missing cells logic
$cell_fix_search = '/<div class="text-muted mt-1" style="font-size: 11px;">🛏️ \{\{ s\.noches \}\} noches<\/div>\s+<div class="text-dark">/s';
$cell_fix_replace = '<div class="text-muted mt-1" style="font-size: 11px;">🛏️ {{ s.noches }} noches</div>
                </td>
                <td class="text-end fw-bold">
                  <div class="text-dark">';
$content = preg_replace($cell_fix_search, $cell_fix_replace, $content);

file_put_contents($file, $content);
echo "File patched successfully.\n";
