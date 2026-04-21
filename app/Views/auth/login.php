<?php
/**
 * app/Views/auth/login.php - PLATINIUM HOTEL BRANDING
 */
require_once __DIR__ . '/../../../rutas.php';
require_once __DIR__ . '/../../../auth/session.php';

if (estaAutenticado()) {
    header('Location: ' . route('index.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — Platinium Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@400;700&family=Inter:wght@300;400;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --platinium-primary: #1A1A1A;
            --platinium-accent: #A68966;
            --platinium-gray: #757575;
            --platinium-neutral: #F5F5F5;
        }

        body,
        html {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: var(--platinium-neutral);
            color: var(--platinium-primary);
            overflow-x: hidden;
        }

        .login-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Left Side: Editorial Image */
        .login-hero {
            flex: 1.2;
            background: linear-gradient(rgba(26, 26, 26, 0.2), rgba(26, 26, 26, 0.2)),
                url('../../../assets/img/edificio.png') center/cover no-repeat;
            display: none;
        }

        @media (min-width: 992px) {
            .login-hero {
                display: block;
            }
        }

        /* Right Side: Form Area */
        .login-content {
            flex: 1;
            background: var(--platinium-neutral);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 5vh 8%;
            position: relative;
            max-height: 100vh;
        }

        .brand-header {
            margin-bottom: 4vh;
        }

        .brand-logo {
            font-family: 'Noto Serif', serif;
            font-size: min(32px, 5vh);
            font-weight: 700;
            color: var(--platinium-primary);
            letter-spacing: 2px;
            text-transform: uppercase;
            border-bottom: 3px solid var(--platinium-accent);
            display: inline-block;
            margin-bottom: 10px;
        }

        .brand-stars {
            color: var(--platinium-accent);
            font-size: 14px;
            margin-left: 10px;
            vertical-align: middle;
        }

        h2 {
            font-family: 'Noto Serif', serif;
            font-weight: 400;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .subtitle {
            color: var(--platinium-gray);
            font-size: 13px;
            margin-bottom: 3vh;
        }

        .form-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--platinium-gray);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .form-control {
            background: transparent;
            border: 0;
            border-bottom: 1px solid #ddd;
            border-radius: 0;
            padding: 10px 0;
            color: var(--platinium-primary);
            transition: all 0.4s ease;
            font-size: 14px;
        }

        .form-control:focus {
            background: transparent;
            border-bottom-color: var(--platinium-accent);
            box-shadow: none;
            color: var(--platinium-primary);
        }

        .btn-platinium {
            background: var(--platinium-accent);
            border: none;
            border-radius: 0;
            padding: 15px;
            font-weight: 700;
            color: #fff;
            width: 100%;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 2vh;
            font-size: 12px;
        }

        .btn-platinium:hover {
            background: var(--platinium-primary);
            color: #fff;
            letter-spacing: 3px;
        }

        .btn-platinium:disabled {
            background: #ccc;
            letter-spacing: 2px;
        }

        .form-check-input {
            border-radius: 0;
            border-color: #ddd;
        }

        .form-check-input:checked {
            background-color: var(--platinium-accent);
            border-color: var(--platinium-accent);
        }

        .footer-copyright {
            margin-top: auto;
            padding-top: 2vh;
            font-size: 10px;
            color: #bbb;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-top: 1px solid #eee;
        }

        /* Smooth reveal */
        .reveal {
            animation: reveal 1s cubic-bezier(0.77, 0, 0.175, 1);
        }

        @keyframes reveal {
            from {
                opacity: 0;
                transform: translateX(30px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .time-stop {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, transparent 18%, rgba(0, 0, 0, 0.96) 100%);
            backdrop-filter: blur(6px);
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity .2s ease;
        }

        .time-text {
            color: white;
            font-size: clamp(44px, 8vw, 84px);
            font-weight: 800;
            letter-spacing: 6px;
            opacity: 0;
            transform: scale(0.5);
            text-shadow: 0 0 18px rgba(255, 255, 255, .35);
        }

        .time-stop.active {
            opacity: 1;
            pointer-events: all;
            animation: aura 1.8s infinite linear;
        }

        .time-stop.active .time-text {
            animation: aparecer 0.45s forwards;
        }

        @keyframes aura {
            0% { filter: hue-rotate(0deg) brightness(1); }
            50% { filter: hue-rotate(180deg) brightness(1.45); }
            100% { filter: hue-rotate(360deg) brightness(1); }
        }

        @keyframes aparecer {
            to {
                opacity: 1;
                transform: scale(1.15);
            }
        }

        .flash {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            background: white;
            opacity: 0;
            z-index: 10000;
            pointer-events: none;
        }

        .flash.active {
            animation: flashAnim 0.32s;
        }

        @keyframes flashAnim {
            0% { opacity: 1; }
            100% { opacity: 0; }
        }
    </style>
</head>

<body>

    <div id="login-app" class="login-wrapper">
        <div class="login-hero"></div>

        <div class="login-content reveal">
            <div class="brand-header">
                <div class="brand-logo">PLATINIUM <span class="brand-stars"><i class="bi bi-star-fill"></i><i
                            class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></span> <br> <span
                        style="font-size:18px; font-weight:400; border:none;">HOTEL</span></div>
            </div>

            <h2>Bienvenido</h2>
            <p class="subtitle">Use sus credenciales corporativas para acceder al sistema.</p>

            <form @submit.prevent="handleSubmit">
                <!-- Alerta SweetAlert manejada por JS -->

                <div class="mb-4">
                    <label class="form-label">Usuario</label>
                    <input v-model="form.usuario" type="text" class="form-control" placeholder="Nombre de usuario"
                        required>
                </div>

                <div class="mb-5">
                    <label class="form-label">Contraseña</label>
                    <input v-model="form.password" type="password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4 small">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label" for="rememberMe" style="color:var(--platinium-gray)">Deseo
                            recordarme</label>
                    </div>
                    <a href="#" style="color:var(--platinium-accent); text-decoration:none; font-weight:600;">¿Problemas
                        con el acceso?</a>
                </div>

                <button type="submit" class="btn btn-platinium" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                    {{ loading ? 'Iniciando Sesión...' : 'Entrar al Sistema' }}
                </button>
            </form>

            <div class="footer-copyright">
                Sistema de Gestión Hotelera v2.1. &nbsp;|&nbsp;
                <a href="#" style="color:#bbb; text-decoration:none;">Soporte</a> &nbsp;|&nbsp;
                <a href="#" style="color:#bbb; text-decoration:none;">Privacidad</a><br>
                &copy; 2026 PLATINIUM HOSPITALITY GROUP. ALL RIGHTS RESERVED.
            </div>
        </div>
    </div>

    <div class="time-stop" id="overlay">
        <div class="time-text">THE WORLD</div>
    </div>
    <div class="flash" id="flash"></div>

    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const { createApp, reactive, ref } = Vue;

        createApp({
            setup() {
                const form = reactive({ usuario: '', password: '' });
                const loading = ref(false);
                const error = ref('');
                const baseUrl = '<?php echo project_base_url(); ?>';
                const overlay = document.getElementById('overlay');
                const flash = document.getElementById('flash');
                const loginAudio = new Audio(baseUrl + 'assets/audio/jotaro.mp3');
                loginAudio.preload = 'auto';

                const ejecutarTheWorldEffect = async () => {
                    flash.classList.add('active');
                    loginAudio.currentTime = 0;
                    loginAudio.play().catch(() => { });

                    await new Promise(resolve => setTimeout(resolve, 200));
                    flash.classList.remove('active');
                    overlay.classList.add('active');
                    document.body.style.pointerEvents = 'none';
                    overlay.style.pointerEvents = 'all';

                    await new Promise(resolve => setTimeout(resolve, 3000));

                    overlay.classList.remove('active');
                    document.body.style.pointerEvents = 'auto';
                    overlay.style.pointerEvents = 'none';
                };

                const handleSubmit = async () => {
                    loading.value = true;
                    error.value = '';
                    try {
                        const res = await axios.post(baseUrl + 'api/auth/login.php', form);
                        if (res.data.ok) {
                            await ejecutarTheWorldEffect();
                            window.location.href = res.data.data.redirect;
                        } else {
                            throw new Error(res.data.msg || 'Error de autenticación');
                        }
                    } catch (err) {
                        const msg = err.response?.data?.msg || err.message || 'Error de autenticación: Verifique sus credenciales.';
                        error.value = msg;
                        Swal.fire({
                            icon: 'error',
                            title: 'Acceso Denegado',
                            text: msg,
                            confirmButtonColor: '#A68966',
                            confirmButtonText: 'Entendido'
                        });
                    } finally {
                        loading.value = false;
                    }
                };

                return { form, loading, error, handleSubmit };
            }
        }).mount('#login-app');
    </script>

</body>

</html>
