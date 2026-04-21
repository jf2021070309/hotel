<?php
/**
 * PREMIUM QA COMMAND CENTER - Categorized 2.1
 * Ubicación: c:\xampp\htdocs\hotel\test_dashboard.php
 */

require_once dirname(__DIR__) . '/config/db.php';

// Asegurar que estamos trabajando sobre la raíz del proyecto para que shell_exec y paths funcionen
chdir(dirname(__DIR__));

if (session_status() === PHP_SESSION_NONE) session_start();

$phpPath     = 'C:\xampp\php\php.exe';
$phpunitPath = 'vendor/phpunit/phpunit/phpunit';
$storagePath = 'tests/results_'; // Prefijo para archivos de resultados

$suitesDefinitions = [
    'Unitarias'   => ['icon' => '🧪', 'desc' => 'Lógica pura, cálculos de turnos y reglas de negocio sin base de datos.'],
    'Integracion' => ['icon' => '🔗', 'desc' => 'Conexión entre módulos (Rooming -> Finanzas) validando persistencia SQL.'],
    'Funcionales'   => ['icon' => '👤', 'desc' => 'Simulación de flujos de usuario completos (Check-in) y Auditoría.']
];

$isRunning = isset($_GET['run']);
$suiteData = [];
$totalPassed = 0;
$totalFailed = 0;

foreach ($suitesDefinitions as $suiteName => $info) {
    $file = $storagePath . $suiteName . '.txt';
    $output = "";
    
    if ($isRunning) {
        $cmd = "$phpPath $phpunitPath --testsuite $suiteName --testdox";
        $output = shell_exec($cmd . " 2>&1");
        file_put_contents($file, $output);
    } else {
        $output = file_exists($file) ? file_get_contents($file) : "";
    }

    $passed = substr_count($output, '✔');
    $failed = substr_count($output, '✘') + substr_count($output, 'FAILURES!');
    
    $totalPassed += $passed;
    $totalFailed += $failed;

    $suiteData[$suiteName] = [
        'output' => $output,
        'passed' => $passed,
        'failed' => $failed,
        'score'  => ($passed + $failed) > 0 ? round(($passed / ($passed + $failed)) * 100) : 0,
        'info'   => $info
    ];
}

$globalScore = ($totalPassed + $totalFailed) > 0 ? round(($totalPassed / ($totalPassed + $totalFailed)) * 100) : 0;

function e($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QA Center | Hotel Premium</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #10b981;
            --accent: #3b82f6;
            --bg: #0b0f1a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --border: rgba(255, 255, 255, 0.1);
            --text: #f8fafc;
            --text-dim: #94a3b8;
            --error: #ef4444;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            background-image: radial-gradient(circle at 0% 0%, rgba(16, 185, 129, 0.1) 0%, transparent 40%);
            color: var(--text);
            margin: 0; padding: 40px 20px;
            display: flex; flex-direction: column; align-items: center;
        }

        .container { max-width: 1100px; width: 100%; }

        header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 32px; padding: 24px; border-radius: 24px;
            background: var(--card-bg); border: 1px solid var(--border);
            backdrop-filter: blur(8px);
        }

        h1 { margin: 0; font-size: 1.5rem; }
        h1 span { background: linear-gradient(135deg, var(--primary), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .global-health {
            display: flex; align-items: center; gap: 20px;
            background: rgba(0,0,0,0.3); padding: 10px 20px; border-radius: 16px;
        }

        .score-mini {
            width: 45px; height: 45px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.9rem;
            background: conic-gradient(var(--primary) <?= $globalScore ?>%, rgba(255,255,255,0.05) 0);
            position: relative;
        }
        .score-mini::before {
            content: ''; position: absolute; inset: 4px;
            background: #1e293b; border-radius: 50%;
        }
        .score-mini span { position: relative; z-index: 1; }

        /* Grid de Suites */
        .suite-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 32px;
        }

        .suite-card {
            background: var(--card-bg); border-radius: 24px; border: 1px solid var(--border);
            padding: 24px; backdrop-filter: blur(12px);
            transition: transform 0.2s;
        }

        .suite-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .suite-icon { font-size: 1.5rem; }
        .suite-score { font-size: 1.2rem; font-weight: 700; color: var(--primary); }
        .suite-score.fail { color: var(--error); }

        .suite-name { font-weight: 700; font-size: 1.1rem; margin-bottom: 8px; display: block; }
        .suite-desc { font-size: 0.8rem; color: var(--text-dim); line-height: 1.4; min-height: 45px; }

        .suite-stats {
            display: flex; gap: 12px; margin-top: 16px; font-size: 0.8rem;
            padding-top: 16px; border-top: 1px solid var(--border);
        }
        .stat-p { color: var(--primary); font-weight: 600; }
        .stat-f { color: var(--error); font-weight: 600; }

        /* Terminal View */
        .console-container {
            background: var(--card-bg); border-radius: 24px; border: 1px solid var(--border);
            padding: 24px;
        }

        .tab-nav { display: flex; gap: 10px; margin-bottom: 16px; overflow-x: auto; padding-bottom: 8px; }
        .tab-btn {
            background: rgba(255,255,255,0.05); border: 1px solid var(--border);
            color: var(--text-dim); padding: 8px 16px; border-radius: 10px;
            cursor: pointer; font-family: inherit; font-size: 0.85rem; transition: 0.2s;
        }
        .tab-btn.active { background: var(--primary); color: #064e3b; font-weight: 600; }

        .terminal {
            background: #000; border-radius: 16px; padding: 20px;
            font-family: 'JetBrains Mono', monospace; font-size: 0.85rem;
            color: #d1d5db; border: 1px solid var(--border);
            min-height: 300px; max-height: 600px; overflow-y: auto; white-space: pre-wrap;
        }

        .line-success { color: var(--primary); }
        .line-error { color: var(--error); }

        .btn-main {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white; border: none; padding: 16px 32px; border-radius: 14px;
            font-weight: 700; cursor: pointer; font-family: inherit;
            box-shadow: 0 10px 20px -10px var(--primary); transition: 0.2s;
            text-decoration: none; display: inline-block; margin-top: 20px;
        }
        .btn-main:hover { transform: translateY(-2px); opacity: 0.9; }

        @media (max-width: 900px) { .suite-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div class="brand">
            <h1>QA <span>Dashboard Centrifix</span></h1>
            <p style="margin: 4px 0 0; font-size: 0.8rem; color: var(--text-dim);">Estructura de Pruebas Profesional v2.1</p>
        </div>
        <div class="global-health">
            <div style="text-align: right;">
                <div style="font-size: 0.7rem; text-transform: uppercase; color: var(--text-dim);">Global Health</div>
                <div style="font-size: 1rem; font-weight: 700;"><?= $globalScore ?>%</div>
            </div>
            <div class="score-mini">
                <span><?= $globalScore ?></span>
            </div>
        </div>
    </header>

    <div class="suite-grid">
        <?php foreach ($suiteData as $name => $data): ?>
            <div class="suite-card">
                <div class="suite-header">
                    <span class="suite-icon"><?= $data['info']['icon'] ?></span>
                    <span class="suite-score <?= $data['failed'] > 0 ? 'fail' : '' ?>"><?= $data['score'] ?>%</span>
                </div>
                <span class="suite-name"><?= $name ?></span>
                <p class="suite-desc"><?= $data['info']['desc'] ?></p>
                <div class="suite-stats">
                    <span class="stat-p">✔ <?= $data['passed'] ?></span>
                    <span class="stat-f">✘ <?= $data['failed'] ?></span>
                    <span style="margin-left:auto; color:var(--text-dim)"><?= ($data['passed']+$data['failed']) ?> tests</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="console-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="margin: 0; font-size: 1.1rem;">Detalle de Ejecución</h2>
            <div class="tab-nav">
                <?php foreach ($suiteData as $name => $data): ?>
                    <button class="tab-btn" onclick="showSuite('<?= $name ?>')" id="btn-<?= $name ?>"><?= $name ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="terminal" id="terminal-content">
            Selecciona una pestaña para ver el log...
        </div>

        <div style="text-align: center;">
            <a href="?run=1" class="btn-main">LANZAR SUITE COMPLETA 🚀</a>
        </div>
    </div>

    <p style="margin-top: 40px; text-align: center; color: var(--text-dim); font-size: 0.8rem;">
        Arquitectura de Pruebas • Antigravity Engineering • <?= date('H:i') ?>
    </p>
</div>

<script>
    const outputs = <?= json_encode(array_map(function($d){
        $out = str_replace('✔', '<span class="line-success">✔</span>', $d['output']);
        $out = str_replace('✘', '<span class="line-error">✘</span>', $out);
        $out = str_replace('FAILURES!', '<b style="color:var(--error)">FAILURES!</b>', $out);
        return $out;
    }, $suiteData)) ?>;

    function showSuite(name) {
        // Update UI
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('btn-' + name).classList.add('active');
        
        // Show output
        const terminal = document.getElementById('terminal-content');
        terminal.innerHTML = outputs[name] || "No hay resultados para esta suite. Ejecuta las pruebas.";
        terminal.scrollTop = 0;
    }

    // Show first suite by default
    <?php if ($isRunning || file_exists($storagePath . 'Unitarias.txt')): ?>
        showSuite('Unitarias');
    <?php endif; ?>
</script>

</body>
</html>
