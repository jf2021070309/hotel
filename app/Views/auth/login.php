<?php
/**
 * app/Views/auth/login.php
 */
require_once __DIR__ . '/../../../rutas.php';
require_once __DIR__ . '/../../../auth/session.php';

// Si ya está logueado, redirigir al dashboard
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
    <title>Login — Hotel Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --primary: #0d6efd; --bg: #f8f9fa; }
        body { background: var(--bg); display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .login-card { background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 40px; width: 100%; max-width: 420px; border: none; }
        .brand-icon { width: 64px; height: 64px; background: var(--primary); color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px; }
        .form-control { border-radius: 10px; padding: 12px 16px; border: 1px solid #dee2e6; transition: all 0.3s; }
        .form-control:focus { box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1); border-color: var(--primary); }
        .btn-login { border-radius: 10px; padding: 12px; font-weight: 600; width: 100%; letter-spacing: 0.5px; transition: all 0.3s; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2); }
    </style>
</head>
<body>

<div id="app-login" class="login-card">
    <div class="text-center">
        <div class="brand-icon"><i class="bi bi-building"></i></div>
        <h3 class="fw-bold">Hotel Manager</h3>
        <p class="text-muted">Ingresa tus credenciales para continuar</p>
    </div>

    <form @submit.prevent="handleLogin" class="mt-4">
        <div v-if="error" class="alert alert-danger py-2" role="alert" style="font-size: 14px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ error }}
        </div>

        <div class="mb-3">
            <label class="form-label text-muted small fw-bold">USUARIO</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-person"></i></span>
                <input v-model="form.usuario" type="text" class="form-control border-start-0" placeholder="Ej: karian" required autocomplete="username">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label text-muted small fw-bold">CONTRASEÑA</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-lock"></i></span>
                <input v-model="form.password" type="password" class="form-control border-start-0" placeholder="••••••••" required autocomplete="current-password">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-login" :disabled="loading">
            <template v-if="loading">
                <span class="spinner-border spinner-border-sm me-2"></span> Cargando...
            </template>
            <template v-else>
                Acceder <i class="bi bi-arrow-right-short ms-1"></i>
            </template>
        </button>
    </form>

    <div class="mt-4 text-center">
        <small class="text-muted">¿Olvidaste tu contraseña? Contacta al administrador.</small>
    </div>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
    const { createApp, ref, reactive } = Vue;

    createApp({
        setup() {
            const form = reactive({ usuario: '', password: '' });
            const loading = ref(false);
            const error = ref('');

            const handleLogin = async () => {
                loading.value = true;
                error.value = '';
                
                try {
                    const res = await axios.post('../../../api/auth/login.php', form);
                    
                    if (res.data.ok) {
                        window.location.href = '../../../' + res.data.data.redirect;
                    }
                } catch (err) {
                    error.value = err.response?.data?.msg || 'Error de conexión con el servidor';
                } finally {
                    loading.value = false;
                }
            };

            return { form, loading, error, handleLogin };
        }
    }).mount('#app-login');
</script>

</body>
</html>
