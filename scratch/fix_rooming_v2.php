<?php
$file = 'c:/xampp/htdocs/hotel/app/Views/rooming/index.php';
$content = file_get_contents($file);

// Main Table Restoration
$tbody_content = '            <tbody>
              <tr v-if="loading">
                <td colspan="7" class="text-center py-5">
                  <div class="spinner-border text-primary"></div>
                </td>
              </tr>
              <tr v-else v-for="s in staysFiltrados" :key="s.id" :class="{\'row-unpaid\': s.estado_pago !== \'pagado\'}">
                <td class="ps-4">
                  <span class="badge bg-light text-dark border fw-bold">#{{ s.id }}</span>
                </td>
                <td>
                  <div class="fw-bold fs-5" style="color: #111;">#{{ s.hab_numero }}</div>
                  <span class="badge" :class="getEstadBadge(s.estado)" style="font-size: 8px; padding: 4px 8px;">{{
                    s.estado.toUpperCase() }}</span>
                  <div class="text-muted small fw-semibold" style="letter-spacing: 0.5px;">{{ s.hab_tipo }}</div>
                </td>
                <td style="width: 250px; max-width: 250px;">
                  <div class="fw-bold" style="white-space: normal; line-height: 1.2; word-break: break-word;">{{ s.titular_nombre || \'---\' }}</div>
                  <div class="text-muted small">Pax: {{ s.pax_total }} personas</div>
                  <div class="mt-1">
                    <span
                      style="font-size:10px; background:#f0f9ff; color:#0369a1; padding:2px 8px; border-radius:20px; font-weight:600; letter-spacing:.3px; border:1px solid #bae6fd;">
                      <i class="bi bi-person-fill-check me-1"></i>{{ s.operador || s.cobrador || \'—\' }}
                    </span>
                  </div>
                </td>
                <td class="small text-nowrap">
                  <div class="mb-1">
                    <span>Ingreso: <span class="fw-bold">{{ fmtFecha(s.fecha_registro) }}</span></span>
                  </div>
                  <div>
                    <span><i class="bi bi-box-arrow-out-right text-danger me-1"></i> Salida: <span class="fw-bold">{{
                        fmtFecha(s.fecha_checkout) }}</span></span>
                  </div>
                  <div class="text-muted mt-1" style="font-size: 11px;">🛏️ {{ s.noches }} noches</div>
                </td>
                <td class="text-end fw-bold">
                  <div class="text-dark">{{ s.moneda_pago == \'USD\' ? \'$\' : (s.moneda_pago == \'CLP\' ? \'P$\' : \'S/\') }} {{ fmtCur(s.total_pago) }}</div>
                  <div class="text-success small" style="font-size: 10px;">Abono {{ s.moneda_pago == \'USD\' ? \'$\' : (s.moneda_pago == \'CLP\' ? \'P$\' : \'S/\') }} {{ fmtCur(s.total_cobrado_orig || s.total_cobrado) }}</div>
                </td>
                <td class="text-center">
                  <span class="badge" :class="getPagoClass(s.estado_pago)" style="font-size: 9px;">{{ s.estado_pago.toUpperCase() }}</span>
                </td>
                <td class="text-center">
                  <span v-if="s.metodo_pago" class="badge bg-light text-dark border fw-bold"
                    style="font-size:9px; padding:4px 7px;">
                    {{ s.metodo_pago }}
                  </span>
                  <span v-else class="text-muted small">—</span>
                </td>
                <td class="text-end pe-4">';

// Find the </thead> and replace everything until the actions btn-group or similar
$content = preg_replace('/<tbody>.*?<td class="text-end pe-4">/s', $tbody_content, $content);

// Final Modal Clean up
$modal_header = '<div class="bg-primary p-4 text-white" style="border-radius:16px 16px 0 0;">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h4 class="mb-0 fw-bold">Habitación #{{ selectedStay.hab_numero }}</h4>
                  <p class="mb-0 opacity-75 small text-uppercase fw-bold">{{ selectedStay.tipo_hab_declarado }}</p>
                </div>
                <span class="badge bg-white text-primary px-3 fs-6 shadow-sm">{{ selectedStay.estado.toUpperCase() }}</span>
              </div>
            </div>';

$content = preg_replace('/<div class="bg-primary p-4 text-white" style="border-radius:16px 16px 0 0;">.*?<div class="p-4">/s', $modal_header . "\n\n            <div class=\"p-4\">", $content);

file_put_contents($file, $content);
echo "Full restoration complete.\n";
