html_content = '''<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Diagrama de Secuencia — CRUD Usuarios</title>
<link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,600,700&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #f7f6f2;
  --surface: #ffffff;
  --surface-2: #f3f0ec;
  --border: rgba(40,37,29,0.10);
  --text: #28251d;
  --text-muted: #7a7974;
  --text-faint: #bab9b4;
  --primary: #01696f;
  --primary-light: #e0f0f0;
  --success: #437a22;
  --success-light: #e8f5e0;
  --warning: #964219;
  --warning-light: #fdf0e8;
  --error: #a12c7b;
  --error-light: #f7e8f3;
  --blue: #006494;
  --blue-light: #e0eef7;
  --purple: #7a39bb;
  --purple-light: #f0e8f8;
  --orange: #da7101;
  --orange-light: #fdf3e0;
  --radius: 10px;
  --shadow: 0 2px 12px rgba(0,0,0,0.07);
}
[data-theme="dark"] {
  --bg: #171614;
  --surface: #1c1b19;
  --surface-2: #22211f;
  --border: rgba(255,255,255,0.08);
  --text: #cdccca;
  --text-muted: #797876;
  --text-faint: #5a5957;
  --primary: #4f98a3;
  --primary-light: #1a3535;
  --success: #6daa45;
  --success-light: #1e3015;
  --warning: #bb653b;
  --warning-light: #2e1a0e;
  --error: #d163a7;
  --error-light: #2a1020;
  --blue: #5591c7;
  --blue-light: #0e2035;
  --purple: #a86fdf;
  --purple-light: #1e1030;
  --orange: #fdab43;
  --orange-light: #2a1800;
  --shadow: 0 2px 16px rgba(0,0,0,0.35);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:'Satoshi',sans-serif;font-size:14px;line-height:1.5;min-height:100vh}

/* HEADER */
.header{background:var(--surface);border-bottom:1px solid var(--border);padding:16px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:var(--shadow)}
.header-left{display:flex;align-items:center;gap:12px}
.logo{width:32px;height:32px}
.header h1{font-size:16px;font-weight:700;color:var(--text)}
.header p{font-size:12px;color:var(--text-muted)}
.theme-toggle{background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:7px 10px;cursor:pointer;color:var(--text-muted);font-size:16px;transition:all 0.2s}
.theme-toggle:hover{color:var(--primary)}

/* NAV TABS */
.nav-tabs{background:var(--surface);border-bottom:1px solid var(--border);padding:0 24px;display:flex;gap:4px;overflow-x:auto}
.tab-btn{padding:12px 16px;border:none;background:none;cursor:pointer;font-family:'Satoshi',sans-serif;font-size:13px;font-weight:500;color:var(--text-muted);border-bottom:2px solid transparent;white-space:nowrap;transition:all 0.2s}
.tab-btn.active{color:var(--primary);border-bottom-color:var(--primary)}
.tab-btn:hover:not(.active){color:var(--text)}

/* MAIN */
.main{padding:24px;max-width:1400px;margin:0 auto}

/* DIAGRAM PANEL */
.diagram-panel{display:none}
.diagram-panel.active{display:block}

/* SEQUENCE DIAGRAM */
.seq-wrapper{overflow-x:auto;padding-bottom:8px}
.seq-diagram{min-width:900px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:32px 24px;box-shadow:var(--shadow)}

/* ACTORS ROW */
.actors-row{display:flex;justify-content:space-around;margin-bottom:0;position:relative}
.actor{display:flex;flex-direction:column;align-items:center;gap:6px;position:relative;z-index:2}
.actor-box{padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;text-align:center;white-space:nowrap;letter-spacing:0.3px;border:2px solid transparent}
.actor-box.browser{background:var(--blue-light);color:var(--blue);border-color:var(--blue)}
.actor-box.vue{background:var(--success-light);color:var(--success);border-color:var(--success)}
.actor-box.middleware{background:var(--warning-light);color:var(--warning);border-color:var(--warning)}
.actor-box.api{background:var(--orange-light);color:var(--orange);border-color:var(--orange)}
.actor-box.controller{background:var(--primary-light);color:var(--primary);border-color:var(--primary)}
.actor-box.model{background:var(--purple-light);color:var(--purple);border-color:var(--purple)}
.actor-box.db{background:var(--error-light);color:var(--error);border-color:var(--error)}
.actor-box.audit{background:var(--surface-2);color:var(--text-muted);border-color:var(--border)}

/* LIFELINES AREA */
.lifelines-area{position:relative;margin-top:0}
.lifelines-svg{width:100%;display:block}

/* LEGEND */
.legend{display:flex;flex-wrap:wrap;gap:12px;margin-top:20px;padding:16px;background:var(--surface-2);border-radius:8px;border:1px solid var(--border)}
.legend-title{width:100%;font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px}
.legend-item{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-muted)}
.legend-line{width:28px;height:2px}
.legend-line.sync{background:var(--text);height:2px}
.legend-line.return{background:var(--text-muted);height:1px;border-top:1px dashed var(--text-muted);background:none}
.legend-line.self{width:14px;height:14px;border:2px solid var(--text-muted);border-left:none;border-radius:0 4px 4px 0;background:none}
.legend-dot{width:8px;height:8px;border-radius:50%}

/* INFO CARDS */
.info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-top:24px}
.info-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px;box-shadow:var(--shadow)}
.info-card h3{font-size:13px;font-weight:700;margin-bottom:8px;display:flex;align-items:center;gap:6px}
.info-card ul{list-style:none;display:flex;flex-direction:column;gap:5px}
.info-card li{font-size:12px;color:var(--text-muted);padding-left:14px;position:relative}
.info-card li::before{content:"→";position:absolute;left:0;color:var(--primary);font-size:11px}
.badge{display:inline-block;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;letter-spacing:0.3px}

/* TITLE SECTION */
.section-title{margin-bottom:20px}
.section-title h2{font-size:20px;font-weight:700;color:var(--text);margin-bottom:4px}
.section-title p{font-size:13px;color:var(--text-muted)}
</style>
</head>
<body>

<header class="header">
  <div class="header-left">
    <svg class="logo" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Hotel Manager">
      <rect width="32" height="32" rx="8" fill="var(--primary)"/>
      <rect x="6" y="14" width="20" height="12" rx="2" fill="white" fill-opacity="0.9"/>
      <rect x="10" y="17" width="4" height="4" rx="1" fill="var(--primary)"/>
      <rect x="18" y="17" width="4" height="4" rx="1" fill="var(--primary)"/>
      <path d="M10 14V10a6 6 0 0 1 12 0v4" stroke="white" stroke-width="2" stroke-linecap="round"/>
      <circle cx="16" cy="10" r="2" fill="white"/>
    </svg>
    <div>
      <h1>Diagrama de Secuencia — CRUD Usuarios</h1>
      <p>Hotel Manager · MVC · PHP + Vue.js + MySQL</p>
    </div>
  </div>
  <button class="theme-toggle" id="themeToggle" aria-label="Cambiar tema">🌙</button>
</header>

<nav class="nav-tabs">
  <button class="tab-btn active" data-tab="crear">Crear Usuario</button>
  <button class="tab-btn" data-tab="listar">Listar Usuarios</button>
  <button class="tab-btn" data-tab="editar">Editar Usuario</button>
  <button class="tab-btn" data-tab="password">Cambiar Password</button>
  <button class="tab-btn" data-tab="toggle">Toggle Estado</button>
  <button class="tab-btn" data-tab="permisos">Gestionar Permisos</button>
</nav>

<main class="main">

<!-- ═══════════════════════════════════════════ CREAR ══ -->
<div class="diagram-panel active" id="panel-crear">
  <div class="section-title">
    <h2>Crear Usuario</h2>
    <p>Flujo completo desde el formulario Vue hasta la BD, incluyendo validación, hash de contraseña y auditoría</p>
  </div>
  <div class="seq-wrapper">
    <div class="seq-diagram">
      <svg id="svg-crear" width="100%" viewBox="0 0 1000 700" xmlns="http://www.w3.org/2000/svg" style="font-family:'Satoshi',sans-serif"></svg>
    </div>
  </div>
  <div class="legend">
    <span class="legend-title">Convenciones del diagrama</span>
    <span class="legend-item"><span class="legend-line sync"></span> Mensaje síncrono (llamada)</span>
    <span class="legend-item"><span style="width:28px;height:0;border-top:2px dashed var(--text-muted);display:inline-block"></span> Retorno / respuesta</span>
    <span class="legend-item"><span class="legend-dot" style="background:var(--success)"></span> Flujo exitoso</span>
    <span class="legend-item"><span class="legend-dot" style="background:var(--error)"></span> Flujo de error / validación</span>
    <span class="legend-item"><span class="legend-dot" style="background:var(--warning)"></span> Condición / alt fragment</span>
  </div>
  <div class="info-grid">
    <div class="info-card">
      <h3><span style="color:var(--success)">✓</span> Validaciones aplicadas</h3>
      <ul>
        <li>Campos obligatorios (usuario, nombre, password)</li>
        <li>Username único verificado en BD</li>
        <li>Password hasheado con BCRYPT antes de INSERT</li>
        <li>Rol por defecto: cajera</li>
      </ul>
    </div>
    <div class="info-card">
      <h3><span style="color:var(--primary)">⟳</span> Objetos participantes</h3>
      <ul>
        <li>Vue App — guardarUsuario()</li>
        <li>Middleware — protegerPorRol()</li>
        <li>UsuarioController — create()</li>
        <li>UsuarioModel — getByUsuario(), create()</li>
        <li>AuditoriaModel — registrar()</li>
        <li>MySQL — INSERT usuarios</li>
      </ul>
    </div>
    <div class="info-card">
      <h3><span style="color:var(--warning)">⚠</span> Flujos alternativos</h3>
      <ul>
        <li>[ALT 1] Campos vacíos → 400 Bad Request</li>
        <li>[ALT 2] Username duplicado → 409 Conflict</li>
        <li>[ALT 3] Sin sesión activa → 401/redirect</li>
        <li>[ALT 4] Error INSERT BD → 500 Internal</li>
      </ul>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════ LISTAR ══ -->
<div class="diagram-panel" id="panel-listar">
  <div class="section-title">
    <h2>Listar Usuarios</h2>
    <p>Carga inicial al montar el componente Vue. Llamada GET que pasa por middleware de sesión y rol</p>
  </div>
  <div class="seq-wrapper">
    <div class="seq-diagram">
      <svg id="svg-listar" width="100%" viewBox="0 0 900 420" xmlns="http://www.w3.org/2000/svg" style="font-family:'Satoshi',sans-serif"></svg>
    </div>
  </div>
  <div class="info-grid">
    <div class="info-card">
      <h3><span style="color:var(--blue)">→</span> Disparador</h3>
      <ul>
        <li>mounted() de Vue llama fetchUsuarios()</li>
        <li>GET /api/usuarios.php?action=listar</li>
        <li>Sin body — solo cookie de sesión</li>
      </ul>
    </div>
    <div class="info-card">
      <h3><span style="color:var(--purple)">⬡</span> Query ejecutada</h3>
      <ul>
        <li>SELECT id, usuario, rol, nombre, estado, created_at</li>
        <li>FROM usuarios</li>
        <li>ORDER BY id DESC</li>
        <li>Sin parámetros — retorna todos</li>
      </ul>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════ EDITAR ══ -->
<div class="diagram-panel" id="panel-editar">
  <div class="section-title">
    <h2>Editar Usuario</h2>
    <p>Actualización con reglas de negocio: protección del admin id=1, auto-protección de sesión propia, y detección de cambios para auditoría</p>
  </div>
  <div class="seq-wrapper">
    <div class="seq-diagram">
      <svg id="svg-editar" width="100%" viewBox="0 0 1000 780" xmlns="http://www.w3.org/2000/svg" style="font-family:'Satoshi',sans-serif"></svg>
    </div>
  </div>
  <div class="info-grid">
    <div class="info-card">
      <h3><span style="color:var(--error)">🛡</span> Reglas de negocio</h3>
      <ul>
        <li>id=1 (admin) — rol no modificable</li>
        <li>Usuario logueado no puede desactivarse</li>
        <li>Username duplicado en otro registro → 409</li>
        <li>Sincroniza $_SESSION si edita su propio perfil</li>
      </ul>
    </div>
    <div class="info-card">
      <h3><span style="color:var(--orange)">📋</span> Auditoría inteligente</h3>
      <ul>
        <li>Captura estado original antes del UPDATE</li>
        <li>Compara campo por campo contra data nueva</li>
        <li>Solo registra campos que realmente cambiaron</li>
        <li>Formatea estado: 0/1 → Inactivo/Activo</li>
      </ul>
    </div>
    <div class="info-card">
      <h3><span style="color:var(--success)">⟳</span> Sincronización de sesión</h3>
      <ul>
        <li>Si id editado === auth_id de sesión actual</li>
        <li>Actualiza $_SESSION auth_nombre, auth_usuario, auth_rol</li>
        <li>Vue llama syncSidebar() para actualizar UI</li>
        <li>Sin necesidad de re-login</li>
      </ul>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════ PASSWORD ══ -->
<div class="diagram-panel" id="panel-password">
  <div class="section-title">
    <h2>Cambiar Contraseña</h2>
    <p>Flujo simplificado: solo actualiza el hash, nunca expone la contraseña anterior</p>
  </div>
  <div class="seq-wrapper">
    <div class="seq-diagram">
      <svg id="svg-password" width="100%" viewBox="0 0 900 500" xmlns="http://www.w3.org/2000/svg" style="font-family:'Satoshi',sans-serif"></svg>
    </div>
  </div>
  <div class="info-grid">
    <div class="info-card">
      <h3><span style="color:var(--primary)">🔒</span> Seguridad</h3>
      <ul>
        <li>Nueva password recibida en texto plano vía HTTPS</li>
        <li>password_hash(pass, PASSWORD_BCRYPT) en el Model</li>
        <li>Nunca se guarda texto plano en BD</li>
        <li>Contraseña anterior NO se verifica (admin action)</li>
      </ul>
    </div>
    <div class="info-card">
      <h3><span style="color:var(--orange)">📋</span> Registro de auditoría</h3>
      <ul>
        <li>Acción: PASS_CAMBIADA</li>
        <li>Módulo: USUARIOS</li>
        <li>Detalle: nombre del usuario afectado</li>
        <li>No registra la contraseña, solo el evento</li>
      </ul>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════ TOGGLE ══ -->
<div class="diagram-panel" id="panel-toggle">
  <div class="section-title">
    <h2>Toggle Estado (Activar/Desactivar)</h2>
    <p>El usuario confirma con SweetAlert2 antes de proceder. Reutiliza el endpoint "editar" con estado invertido</p>
  </div>
  <div class="seq-wrapper">
    <div class="seq-diagram">
      <svg id="svg-toggle" width="100%" viewBox="0 0 1000 560" xmlns="http://www.w3.org/2000/svg" style="font-family:'Satoshi',sans-serif"></svg>
    </div>
  </div>
  <div class="info-grid">
    <div class="info-card">
      <h3><span style="color:var(--warning)">⚡</span> Protección en frontend</h3>
      <ul>
        <li>Compara u.id === authUser.id antes de llamar API</li>
        <li>Muestra Swal.fire de confirmación</li>
        <li>Si cancela → no se envía nada al servidor</li>
        <li>Bug conocido: no verifica res.data.ok</li>
      </ul>
    </div>
    <div class="info-card">
      <h3><span style="color:var(--primary)">⟳</span> Reutilización de endpoint</h3>
      <ul>
        <li>Usa action=editar (no hay action=toggle)</li>
        <li>Envía {...u, estado: nuevoEstado}</li>
        <li>El controller aplica mismas validaciones del update</li>
        <li>Registra auditoría ACTUALIZAR_USUARIO</li>
      </ul>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════ PERMISOS ══ -->
<div class="diagram-panel" id="panel-permisos">
  <div class="section-title">
    <h2>Gestionar Permisos de Módulos</h2>
    <p>Flujo ACL: carga la configuración actual de módulos del usuario y persiste los cambios de acceso</p>
  </div>
  <div class="seq-wrapper">
    <div class="seq-diagram">
      <svg id="svg-permisos" width="100%" viewBox="0 0 1000 580" xmlns="http://www.w3.org/2000/svg" style="font-family:'Satoshi',sans-serif"></svg>
    </div>
  </div>
  <div class="info-grid">
    <div class="info-card">
      <h3><span style="color:var(--purple)">⬡</span> Módulo separado</h3>
      <ul>
        <li>Llama a api/permisos.php (no usuarios.php)</li>
        <li>action=listar — GET con usuario_id param</li>
        <li>action=guardar — POST con array de módulos</li>
        <li>Botón oculto si u.rol === "admin"</li>
      </ul>
    </div>
    <div class="info-card">
      <h3><span style="color:var(--error)">⚠</span> Archivo faltante</h3>
      <ul>
        <li>api/permisos.php no fue mostrado en el código</li>
        <li>Necesita PermisosController + PermisosModel</li>
        <li>Tabla: permisos_modulos (usuario_id, modulo, activo)</li>
        <li>Si no existe → modal con spinner infinito</li>
      </ul>
    </div>
  </div>
</div>

</main>

<script>
// ─── THEME TOGGLE ─────────────────────────────────
const html = document.documentElement;
const btn  = document.getElementById('themeToggle');
let theme  = 'light';
btn.addEventListener('click', () => {
  theme = theme === 'light' ? 'dark' : 'light';
  html.setAttribute('data-theme', theme);
  btn.textContent = theme === 'dark' ? '☀️' : '🌙';
  drawAll();
});

// ─── TAB SWITCHING ────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(b => {
  b.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(x => x.classList.remove('active'));
    document.querySelectorAll('.diagram-panel').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    document.getElementById('panel-' + b.dataset.tab).classList.add('active');
  });
});

// ─── COLOR HELPERS ────────────────────────────────
function css(v) {
  return getComputedStyle(document.documentElement).getPropertyValue(v).trim();
}

// ─── SVG DRAW HELPERS ─────────────────────────────
function makeSVG(id, actors, messages, h) {
  const svg = document.getElementById(id);
  if (!svg) return;
  svg.innerHTML = '';

  const W      = 1000;
  const TOP    = 60;   // actor boxes top
  const LH     = 24;   // lifeline header height
  const MSGSY  = TOP + LH + 30; // first message Y
  const MSTEP  = 44;  // vertical step per message

  const cols   = actors.length;
  const colW   = W / cols;
  const cx     = actors.map((_, i) => colW * i + colW / 2);

  const C = {
    text:     css('--text'),
    muted:    css('--text-muted'),
    faint:    css('--text-faint'),
    border:   css('--border'),
    surface2: css('--surface-2'),
    bg:       css('--bg'),
  };

  // Actor color maps
  const aclr = {
    browser:    { bg: css('--blue-light'),   fg: css('--blue')   },
    vue:        { bg: css('--success-light'),fg: css('--success')},
    middleware: { bg: css('--warning-light'),fg: css('--warning')},
    api:        { bg: css('--orange-light'), fg: css('--orange') },
    controller: { bg: css('--primary-light'),fg: css('--primary')},
    model:      { bg: css('--purple-light'), fg: css('--purple') },
    db:         { bg: css('--error-light'),  fg: css('--error')  },
    audit:      { bg: C.surface2,            fg: C.muted        },
  };

  const ns = 'http://www.w3.org/2000/svg';
  function el(tag, attrs={}, text='') {
    const e = document.createElementNS(ns, tag);
    Object.entries(attrs).forEach(([k,v]) => e.setAttribute(k,v));
    if (text) e.textContent = text;
    return e;
  }

  // Arrow marker
  const defs = el('defs');
  ['solid','dashed'].forEach(t => {
    const marker = el('marker', {id:`arrow-${t}-${id}`, markerWidth:'8', markerHeight:'8', refX:'6', refY:'3', orient:'auto'});
    const path   = el('path', {d:'M0,0 L0,6 L8,3 z', fill: t==='solid'?C.text:C.muted});
    marker.appendChild(path); defs.appendChild(marker);
  });
  svg.appendChild(defs);

  // Activation fragment rects
  const activations = {};
  messages.forEach((m, mi) => {
    const y = MSGSY + mi * MSTEP;
    if (m.activate !== undefined) {
      activations[m.activate] = activations[m.activate] || [];
      activations[m.activate].push(y);
    }
  });

  // Draw lifelines (vertical dashed lines)
  const lifelineH = MSGSY + messages.length * MSTEP + 30;
  actors.forEach((a, i) => {
    const x = cx[i];
    const line = el('line', {
      x1: x, y1: TOP + LH, x2: x, y2: lifelineH,
      stroke: C.faint, 'stroke-width': '1.5',
      'stroke-dasharray': '6,4'
    });
    svg.appendChild(line);
  });

  // Draw actor boxes
  actors.forEach((a, i) => {
    const x  = cx[i];
    const bw = 110, bh = 28;
    const ac = aclr[a.type] || aclr.model;

    // Box
    const rect = el('rect', {
      x: x - bw/2, y: TOP - bh/2, width: bw, height: bh,
      rx: 6, fill: ac.bg, stroke: ac.fg, 'stroke-width': '1.5'
    });
    svg.appendChild(rect);

    // Label
    const txt = el('text', {
      x, y: TOP + 5,
      'text-anchor': 'middle', fill: ac.fg,
      'font-size': '11', 'font-weight': '700', 'font-family': 'Satoshi,sans-serif'
    }, a.label);
    svg.appendChild(txt);

    // Bottom box
    const rect2 = el('rect', {
      x: x - bw/2, y: lifelineH - bh/2, width: bw, height: bh,
      rx: 6, fill: ac.bg, stroke: ac.fg, 'stroke-width': '1.5'
    });
    svg.appendChild(rect2);
    const txt2 = el('text', {
      x, y: lifelineH + 5,
      'text-anchor': 'middle', fill: ac.fg,
      'font-size': '11', 'font-weight': '700', 'font-family': 'Satoshi,sans-serif'
    }, a.label);
    svg.appendChild(txt2);
  });

  // Draw messages
  messages.forEach((m, mi) => {
    const y     = MSGSY + mi * MSTEP;
    const isDash = m.type === 'return' || m.type === 'dash';
    const isSelf = m.from === m.to;
    const x1    = cx[m.from];
    const x2    = cx[m.to];

    if (isSelf) {
      // Self-call loop
      const lx = x1 + 12;
      const path = el('path', {
        d: `M${x1},${y} Q${lx+30},${y} ${lx+30},${y+12} Q${lx+30},${y+24} ${x2},${y+24}`,
        fill: 'none', stroke: isDash ? C.muted : C.text,
        'stroke-width': '1.5',
        'stroke-dasharray': isDash ? '5,3' : 'none',
        'marker-end': `url(#arrow-${isDash?'dashed':'solid'}-${id})`
      });
      svg.appendChild(path);
      const label = el('text', {
        x: x1 + 36, y: y + 14,
        'text-anchor': 'start', fill: isDash ? C.muted : C.text,
        'font-size': '11', 'font-family': 'Satoshi,sans-serif'
      }, m.label);
      svg.appendChild(label);
      return;
    }

    // Draw activation box on target
    if (m.type === 'call') {
      const aw = 10;
      const abox = el('rect', {
        x: x2 - aw/2, y: y - 4, width: aw, height: 20,
        rx: 2, fill: css('--primary-light'), stroke: css('--primary'), 'stroke-width': '1'
      });
      svg.appendChild(abox);
    }

    // Fragment label (alt/opt/loop)
    if (m.fragment) {
      const fw = Math.abs(x2 - x1) + 60;
      const fx = Math.min(x1, x2) - 30;
      const frect = el('rect', {
        x: fx, y: y - 14, width: fw, height: m.fragmentH || MSTEP * 1.2,
        rx: 4, fill: 'none',
        stroke: m.fragmentColor || C.faint,
        'stroke-width': '1', 'stroke-dasharray': '4,3'
      });
      svg.appendChild(frect);
      const ftab = el('rect', {x: fx, y: y-14, width:32, height:14, rx:'2 0 0 0', fill: m.fragmentColor || C.faint, opacity:'0.3'});
      svg.appendChild(ftab);
      const ftxt = el('text', {
        x: fx+4, y: y-3, fill: m.fragmentColor || C.muted,
        'font-size': '9', 'font-weight': '700', 'font-family': 'Satoshi,sans-serif',
        'text-transform': 'uppercase'
      }, m.fragment);
      svg.appendChild(ftxt);
      if (m.fragmentLabel) {
        const ftxt2 = el('text', {
          x: fx+36, y: y-3, fill: m.fragmentColor || C.muted,
          'font-size': '9', 'font-family': 'Satoshi,sans-serif'
        }, '[' + m.fragmentLabel + ']');
        svg.appendChild(ftxt2);
      }
    }

    // Arrow line
    const line = el('line', {
      x1, y1: y, x2: x1 < x2 ? x2 - 6 : x2 + 6, y2: y,
      stroke: isDash ? C.muted : C.text,
      'stroke-width': isDash ? '1.5' : '2',
      'stroke-dasharray': isDash ? '5,3' : 'none',
      'marker-end': `url(#arrow-${isDash?'dashed':'solid'}-${id})`
    });
    svg.appendChild(line);

    // Message label
    const lx = (x1 + x2) / 2;
    const ly = y - 5;
    const labelBg = el('rect', {
      x: lx - (m.label.length * 3.2), y: ly - 11,
      width: m.label.length * 6.4 + 8, height: 14,
      rx: 3, fill: css('--bg'), opacity: '0.85'
    });
    svg.appendChild(labelBg);
    const label = el('text', {
      x: lx, y: ly,
      'text-anchor': 'middle',
      fill: isDash ? C.muted : C.text,
      'font-size': '11',
      'font-family': 'Satoshi,sans-serif',
      'font-weight': isDash ? '400' : '600'
    }, m.label);
    svg.appendChild(label);

    // Sequence number
    if (m.num) {
      const numTxt = el('text', {
        x: x1 < x2 ? x1 + 6 : x1 - 6, y: y - 5,
        'text-anchor': x1 < x2 ? 'start' : 'end',
        fill: css('--primary'), 'font-size': '10',
        'font-weight': '700', 'font-family': 'Satoshi,sans-serif'
      }, m.num + '.');
      svg.appendChild(numTxt);
    }

    // Note
    if (m.note) {
      const notex = x1 < x2 ? Math.max(x1,x2) + 10 : Math.min(x1,x2) - 10;
      const noteanchor = x1 < x2 ? 'start' : 'end';
      const noterect = el('rect', {
        x: x1 < x2 ? notex - 2 : notex - m.note.length*6 - 2,
        y: y - 10, width: m.note.length * 6 + 8, height: 16,
        rx: 4, fill: css('--warning-light'), stroke: css('--warning'), 'stroke-width': '0.5', opacity:'0.8'
      });
      svg.appendChild(noterect);
      const notetxt = el('text', {
        x: x1 < x2 ? notex + 2 : notex - m.note.length*6 + 2,
        y: y + 2, 'text-anchor': 'start',
        fill: css('--warning'), 'font-size': '9', 'font-family': 'Satoshi,sans-serif'
      }, m.note);
      svg.appendChild(notetxt);
    }
  });

  // Update SVG viewBox height
  const totalH = MSGSY + messages.length * MSTEP + 70;
  svg.setAttribute('viewBox', `0 0 ${W} ${totalH}`);
}

// ═══════════════════════════════════════════════════════
// DATA — cada flujo
// ═══════════════════════════════════════════════════════

function drawCrear() {
  const actors = [
    {label:'Browser/Admin', type:'browser'},
    {label:'Vue App', type:'vue'},
    {label:'Middleware', type:'middleware'},
    {label:'API Router', type:'api'},
    {label:'Controller', type:'controller'},
    {label:'UsuarioModel', type:'model'},
    {label:'AuditoriaModel', type:'audit'},
    {label:'MySQL', type:'db'},
  ];
  const msgs = [
    {from:0,to:1,num:'1',label:'click "Nuevo Usuario"'},
    {from:1,to:1,num:'2',label:'nuevaUsuario() — resetea form'},
    {from:0,to:1,num:'3',label:'submit form (POST data)'},
    {from:1,to:1,num:'4',label:'guardarUsuario() — valida campos UI',type:'call'},
    {from:1,to:3,num:'5',label:'POST /api/usuarios.php?action=crear'},
    {from:3,to:2,num:'6',label:'protegerPorRol("cajera","gestion_usuarios")'},
    {from:2,to:3,type:'return',label:'ok — sesión válida + rol OK'},
    {from:3,to:4,num:'7',label:'controller.create($input)'},
    {from:4,to:4,num:'8',label:'valida campos obligatorios',fragment:'opt',fragmentLabel:'campos vacíos → return 400',fragmentColor:css('--error'),fragmentH:46},
    {from:4,to:5,num:'9',label:'model.getByUsuario($usuario)'},
    {from:5,to:7,num:'10',label:'SELECT WHERE usuario=? AND estado=1'},
    {from:7,to:5,type:'return',label:'null (no existe)'},
    {from:5,to:4,type:'return',label:'null'},
    {from:4,to:4,num:'11',label:'username duplicado → return 409',fragment:'opt',fragmentLabel:'usuario ya existe',fragmentColor:css('--error'),fragmentH:46},
    {from:4,to:5,num:'12',label:'model.create($data)',note:'bcrypt aquí'},
    {from:5,to:7,num:'13',label:'INSERT INTO usuarios (hash BCRYPT)'},
    {from:7,to:5,type:'return',label:'lastInsertId()'},
    {from:5,to:4,type:'return',label:'$id (int)'},
    {from:4,to:4,num:'14',label:'obtenerUsuarioActual()'},
    {from:4,to:6,num:'15',label:'audit.registrar(USUARIO_CREADO)'},
    {from:6,to:7,num:'16',label:'INSERT INTO auditoria'},
    {from:7,to:6,type:'return',label:'ok'},
    {from:4,to:3,type:'return',label:"['ok'=>true,'id'=>X]"},
    {from:3,to:1,type:'return',label:'json {ok:true, msg:"Usuario creado"}'},
    {from:1,to:1,num:'17',label:'showToast success + fetchUsuarios()'},
  ];
  makeSVG('svg-crear', actors, msgs, 800);
}

function drawListar() {
  const actors = [
    {label:'Vue App', type:'vue'},
    {label:'Middleware', type:'middleware'},
    {label:'API Router', type:'api'},
    {label:'Controller', type:'controller'},
    {label:'UsuarioModel', type:'model'},
    {label:'MySQL', type:'db'},
  ];
  const msgs = [
    {from:0,to:0,num:'1',label:'mounted() → fetchUsuarios()'},
    {from:0,to:2,num:'2',label:'GET /api/usuarios.php?action=listar'},
    {from:2,to:1,num:'3',label:'protegerPorRol("cajera","gestion_usuarios")'},
    {from:1,to:2,type:'return',label:'sesión ok'},
    {from:2,to:3,num:'4',label:'controller.index()'},
    {from:3,to:4,num:'5',label:'model.getAll()'},
    {from:4,to:5,num:'6',label:'SELECT id,usuario,rol,nombre,estado,created_at ORDER BY id DESC'},
    {from:5,to:4,type:'return',label:'array de usuarios'},
    {from:4,to:3,type:'return',label:'array'},
    {from:3,to:2,type:'return',label:'array'},
    {from:2,to:0,type:'return',label:'json {ok:true, data:[...]}'},
    {from:0,to:0,num:'7',label:'this.usuarios = res.data.data — renderiza tabla'},
  ];
  makeSVG('svg-listar', actors, msgs, 420);
}

function drawEditar() {
  const actors = [
    {label:'Browser/Admin', type:'browser'},
    {label:'Vue App', type:'vue'},
    {label:'Middleware', type:'middleware'},
    {label:'API Router', type:'api'},
    {label:'Controller', type:'controller'},
    {label:'UsuarioModel', type:'model'},
    {label:'AuditoriaModel', type:'audit'},
    {label:'MySQL', type:'db'},
  ];
  const msgs = [
    {from:0,to:1,num:'1',label:'click Editar → abrirModalEditar(u)'},
    {from:1,to:1,num:'2',label:'this.current = {...u} — editMode=true'},
    {from:0,to:1,num:'3',label:'submit form editar'},
    {from:1,to:3,num:'4',label:'POST ?action=editar (JSON)'},
    {from:3,to:2,num:'5',label:'protegerPorRol()'},
    {from:2,to:3,type:'return',label:'ok'},
    {from:3,to:4,num:'6',label:'controller.update($id, $data)'},
    {from:4,to:4,num:'7',label:'Guard: id===1 && rol!="admin" → 403',fragment:'opt',fragmentLabel:'admin protegido',fragmentColor:css('--error'),fragmentH:46},
    {from:4,to:4,num:'8',label:'Guard: $id===auth_id && estado==0 → 403',fragment:'opt',fragmentLabel:'auto-protección',fragmentColor:css('--error'),fragmentH:46},
    {from:4,to:7,num:'9',label:'SELECT id WHERE usuario=? AND id!=?'},
    {from:7,to:4,type:'return',label:'null (sin duplicado)'},
    {from:4,to:5,num:'10',label:'model.getById($id) — captura original'},
    {from:5,to:7,num:'11',label:'SELECT * FROM usuarios WHERE id=?'},
    {from:7,to:5,type:'return',label:'$original array'},
    {from:5,to:4,type:'return',label:'$original'},
    {from:4,to:5,num:'12',label:'model.update($id, $data)'},
    {from:5,to:7,num:'13',label:'UPDATE usuarios SET ... WHERE id=?'},
    {from:7,to:5,type:'return',label:'true'},
    {from:4,to:4,num:'14',label:'Si $id===auth_id → sync $_SESSION'},
    {from:4,to:4,num:'15',label:'Detecta cambios (diff $original vs $data)'},
    {from:4,to:6,num:'16',label:'audit.registrar(ACTUALIZAR_USUARIO, cambios JSON)'},
    {from:6,to:7,num:'17',label:'INSERT INTO auditoria'},
    {from:4,to:3,type:'return',label:"['ok'=>true]"},
    {from:3,to:1,type:'return',label:'json {ok:true}'},
    {from:1,to:1,num:'18',label:'fetchUsuarios() + syncSidebar() si es propio'},
  ];
  makeSVG('svg-editar', actors, msgs, 840);
}

function drawPassword() {
  const actors = [
    {label:'Browser/Admin', type:'browser'},
    {label:'Vue App', type:'vue'},
    {label:'Middleware', type:'middleware'},
    {label:'API Router', type:'api'},
    {label:'Controller', type:'controller'},
    {label:'UsuarioModel', type:'model'},
    {label:'AuditoriaModel', type:'audit'},
    {label:'MySQL', type:'db'},
  ];
  const msgs = [
    {from:0,to:1,num:'1',label:'click "Cambiar Contraseña"'},
    {from:1,to:1,num:'2',label:'abrirModalPass(u) — muestra modal'},
    {from:0,to:1,num:'3',label:'submit nueva password'},
    {from:1,to:3,num:'4',label:'POST ?action=cambiar_pass {id, password}'},
    {from:3,to:2,num:'5',label:'protegerPorRol()'},
    {from:2,to:3,type:'return',label:'ok'},
    {from:3,to:4,num:'6',label:'controller.updatePassword($id, $pass)'},
    {from:4,to:4,num:'7',label:'Guard: !$id || empty($pass) → 400',fragment:'opt',fragmentLabel:'datos inválidos',fragmentColor:css('--error'),fragmentH:46},
    {from:4,to:5,num:'8',label:'model.updatePassword($id, $pass)',note:'bcrypt aquí'},
    {from:5,to:7,num:'9',label:'UPDATE usuarios SET password=HASH WHERE id=?'},
    {from:7,to:5,type:'return',label:'true'},
    {from:5,to:4,type:'return',label:'true'},
    {from:4,to:5,num:'10',label:'model.getById($id) — para nombre en log'},
    {from:5,to:4,type:'return',label:'$target [nombre]'},
    {from:4,to:6,num:'11',label:'audit.registrar(PASS_CAMBIADA)'},
    {from:6,to:7,num:'12',label:'INSERT INTO auditoria'},
    {from:4,to:3,type:'return',label:"['ok'=>true]"},
    {from:3,to:1,type:'return',label:'json {ok:true}'},
    {from:1,to:1,num:'13',label:'showToast + cierra modal'},
  ];
  makeSVG('svg-password', actors, msgs, 560);
}

function drawToggle() {
  const actors = [
    {label:'Browser/Admin', type:'browser'},
    {label:'Vue App', type:'vue'},
    {label:'SweetAlert2', type:'middleware'},
    {label:'Middleware', type:'middleware'},
    {label:'API Router', type:'api'},
    {label:'Controller', type:'controller'},
    {label:'MySQL', type:'db'},
  ];
  const msgs = [
    {from:0,to:1,num:'1',label:'click Toggle Estado'},
    {from:1,to:1,num:'2',label:'Guard: u.id===authUser.id → Swal.fire error',fragment:'opt',fragmentLabel:'auto-protección JS',fragmentColor:css('--error'),fragmentH:46},
    {from:1,to:2,num:'3',label:'Swal.fire confirm dialog'},
    {from:2,to:1,type:'return',label:'result.isConfirmed'},
    {from:1,to:1,num:'4',label:'nuevoEstado = u.estado==1 ? 0 : 1',fragment:'opt',fragmentLabel:'cancelar → fin',fragmentColor:css('--warning'),fragmentH:46},
    {from:1,to:4,num:'5',label:'POST ?action=editar {...u, estado: nuevoEstado}'},
    {from:4,to:3,num:'6',label:'protegerPorRol()'},
    {from:3,to:4,type:'return',label:'ok'},
    {from:4,to:5,num:'7',label:'controller.update($id, $data)'},
    {from:5,to:6,num:'8',label:'UPDATE usuarios SET estado=? WHERE id=?'},
    {from:6,to:5,type:'return',label:'true'},
    {from:5,to:4,type:'return',label:"['ok'=>true]"},
    {from:4,to:1,type:'return',label:'json {ok:true}'},
    {from:1,to:1,num:'9',label:'showToast + fetchUsuarios()'},
  ];
  makeSVG('svg-toggle', actors, msgs, 600);
}

function drawPermisos() {
  const actors = [
    {label:'Browser/Admin', type:'browser'},
    {label:'Vue App', type:'vue'},
    {label:'Middleware', type:'middleware'},
    {label:'API Permisos', type:'api'},
    {label:'PermisosCtrl', type:'controller'},
    {label:'PermisosModel', type:'model'},
    {label:'MySQL', type:'db'},
  ];
  const msgs = [
    {from:0,to:1,num:'1',label:'click "Gestionar Módulos"'},
    {from:1,to:1,num:'2',label:'abrirPermisos(u) — abre modal + loadingPermisos=true'},
    {from:1,to:3,num:'3',label:'GET /api/permisos.php?action=listar&usuario_id=X'},
    {from:3,to:2,num:'4',label:'protegerPorRol()'},
    {from:2,to:3,type:'return',label:'ok'},
    {from:3,to:4,num:'5',label:'controller.listar($usuario_id)'},
    {from:4,to:5,num:'6',label:'model.getPermisosUsuario($usuario_id)'},
    {from:5,to:6,num:'7',label:'SELECT modulo,activo FROM permisos_modulos WHERE usuario_id=?'},
    {from:6,to:5,type:'return',label:'array de módulos'},
    {from:5,to:4,type:'return',label:'array'},
    {from:4,to:3,type:'return',label:'array con icons + labels'},
    {from:3,to:1,type:'return',label:'json {ok:true, data:[...modulos]}'},
    {from:1,to:1,num:'8',label:'permisosModulos = data — renderiza switches'},
    {from:0,to:1,num:'9',label:'toggle switches + click "Guardar Permisos"'},
    {from:1,to:3,num:'10',label:'POST ?action=guardar {usuario_id, permisos:[]}'},
    {from:3,to:4,num:'11',label:'controller.guardar($data)'},
    {from:4,to:5,num:'12',label:'model.upsertPermisos($usuario_id, $permisos)'},
    {from:5,to:6,num:'13',label:'INSERT ... ON DUPLICATE KEY UPDATE activo=?'},
    {from:6,to:5,type:'return',label:'ok'},
    {from:5,to:4,type:'return',label:'true'},
    {from:4,to:3,type:'return',label:"['ok'=>true]"},
    {from:3,to:1,type:'return',label:'json {ok:true}'},
    {from:1,to:1,num:'14',label:'cierra modal + showToast'},
  ];
  makeSVG('svg-permisos', actors, msgs, 620);
}

function drawAll() {
  drawCrear();
  drawListar();
  drawEditar();
  drawPassword();
  drawToggle();
  drawPermisos();
}

// Initial draw
drawAll();

// Redraw on resize
window.addEventListener('resize', drawAll);
</script>
</body>
</html>'''

with open('sequence-diagram-usuarios.html', 'w', encoding='utf-8') as f:
    f.write(html_content)
print("OK")