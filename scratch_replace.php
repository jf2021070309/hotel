<?php
$viewsDir = __DIR__ . '/app/Views';
$filesToCheck = [
    'clientes/v2.php',
    'clientes/index.php',
    'limpieza/v2.php',
    'limpieza/index.php',
    'rooming/v2.php',
    'rooming/index.php',
    'flujo/v2.php',
    'flujo/index.php',
    'flujo/dia.php',
    'flujo/form.php',
    'inventario/index.php',
    'inventario/historial.php',
    'habitaciones/index.php',
    'habitaciones/crear.php',
    'habitaciones/editar.php',
    'yape/index.php',
    'yape/form.php',
    'reservas/index.php',
    'calculadora/index.php',
    'dashboard/admin.php',
    'dashboard/cajera.php',
    'admin/auditoria.php',
    'admin/medios_pago.php',
    'admin/usuarios.php',
    'caja_chica/detalle.php'
];

foreach ($filesToCheck as $relPath) {
    $file = $viewsDir . '/' . $relPath;
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);

    // Encuentra el topbar (desde <div class="topbar" hasta el primer </div> que esté al mismo nivel o simplemente extraemos la cabecera por partes).
    // Para simplificar, usemos regex para extraer los datos del topbar actual.
    // Típicamente los títulos están en <h5> o <h6> o <h4>.
    preg_match('/<h[456][^>]*>(.*?)<\/h[456]>/is', $content, $mTitle);
    $title = $mTitle ? trim(strip_tags($mTitle[1])) : 'Título';
    
    // Subtítulo en <p> o <div> después del título
    preg_match('/<p[^>]*>(.*?)<\/p>|<div class="text-white-50"[^>]*>(.*?)<\/div>/is', $content, $mSub);
    $subtitle = '';
    if ($mSub) {
        $subtitle = trim(strip_tags(!empty($mSub[1]) ? $mSub[1] : $mSub[2]));
    }
    
    // Icono
    preg_match('/<i class="bi (bi-[^"]+)/is', $content, $mIcon);
    $icon = $mIcon ? $mIcon[1] : 'bi-app';
    
    // Si la cadena de búsqueda del icono encontró algo raro, limpiamos:
    $icon = preg_replace('/\s.*/', '', $icon);

    // Botón de acción principal en la topbar:
    // Típicamente es Actualizar, Volver, etc.
    // Buscamos un botón en el ms-auto o justify-content-between.
    preg_match('/<button[^>]*?(?:@click|onclick)="([^"]+)"[^>]*>.*?<span[^>]*>(.*?)<\/span>.*?<\/button>/is', $content, $mBtn);
    if (!$mBtn) {
        preg_match('/<button[^>]*?(?:@click|onclick)="([^"]+)"[^>]*>.*?<\/button>/is', $content, $mBtn2);
        if ($mBtn2) {
            $actionAttr = preg_match('/@click/', $mBtn2[0]) ? '@click="' . $mBtn2[1] . '"' : 'onclick="' . $mBtn2[1] . '"';
            $actionText = preg_match('/Volver/i', $mBtn2[0]) ? 'Volver' : 'Actualizar';
            $btnIcon = preg_match('/bi-arrow-left/i', $mBtn2[0]) ? 'bi-arrow-left' : 'bi-arrow-clockwise';
        } else {
            $actionAttr = 'onclick="window.location.reload()"';
            $actionText = 'Actualizar';
            $btnIcon = 'bi-arrow-clockwise';
        }
    } else {
        $actionAttr = preg_match('/@click/', $mBtn[0]) ? '@click="' . $mBtn[1] . '"' : 'onclick="' . $mBtn[1] . '"';
        $actionText = trim($mBtn[2]);
        $btnIcon = preg_match('/bi-arrow-left/i', $mBtn[0]) ? 'bi-arrow-left' : 'bi-arrow-clockwise';
    }
    
    if (strpos($actionAttr, 'openSidebar') !== false || strpos($actionAttr, 'handleMenuClick') !== false) {
        // Encontró el botón del sidebar, mejor buscamos el otro
        preg_match_all('/<button[^>]*?(?:@click|onclick)="([^"]+)"[^>]*>/is', $content, $mBtnsAll);
        $actionAttr = 'onclick="window.location.reload()"';
        $actionText = 'Actualizar';
        $btnIcon = 'bi-arrow-clockwise';
        foreach ($mBtnsAll[1] as $idx => $act) {
            if (strpos($act, 'Sidebar') === false && strpos($act, 'MenuClick') === false) {
                $btnHtml = $mBtnsAll[0][$idx];
                $actionAttr = preg_match('/@click/', $btnHtml) ? '@click="' . $act . '"' : 'onclick="' . $act . '"';
                if (preg_match('/<span[^>]*>(.*?)<\/span>/is', $content, $mt)) {
                    // we can just stick to default
                }
            }
        }
    }

    $newTopbar = '<div class="topbar" style="background-color: #111827; padding: 0.75rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
    <div class="d-flex align-items-center justify-content-between w-100">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-dark btn-sm rounded-circle d-md-none" onclick="handleMenuClick()" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.1); border: none;">
          <i class="bi bi-list text-white"></i>
        </button>
        <div class="d-flex align-items-center gap-3">
            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #f59e0b, #d97706); display: flex; align-items: center; justify-content: center; box-shadow: 0 0 15px rgba(245, 158, 11, 0.4);">
                <i class="bi ' . $icon . ' text-white fs-5"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-white" style="font-size: 18px; letter-spacing: -0.5px;">' . $title . '</h4>
                <div class="text-white-50" style="font-size: 11px;">' . $subtitle . '</div>
            </div>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2">
          <button ' . $actionAttr . ' class="btn btn-sm btn-outline-light d-flex align-items-center gap-2" style="font-size: 12px; padding: 4px 12px; border-color: rgba(255,255,255,0.2);">
              <i class="bi ' . $btnIcon . '"></i> <span class="d-none d-md-inline">' . $actionText . '</span>
          </button>
      </div>
    </div>
  </div>';

    // Ahora hay que buscar el topbar original y reemplazarlo.
    // El topbar suele ir de <div class="topbar hasta el siguiente </div> que cierre ms-auto o hasta <div class="page-body
    // Es más seguro extraer entre <div class="topbar (o parecido) y <div class="page-body
    // o hacer un reemplazo de string manualmente.
    $pattern = '/<div class="topbar[^>]*>.*?<\/div>\s*<\/div>\s*<\/div>/is';
    // wait, HTML nested divs are hard with regex. Let's do it with DOMDocument or just simple string parsing.
}
?>
