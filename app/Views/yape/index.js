/**
 * app/Views/yape/index.js
 */
const { createApp, ref, onMounted } = Vue;

createApp({
  setup() {
    const BASE = '../../../api/yape.php?action=';

    const loading = ref(true);
    const registros = ref([]);
    
    // Config filtros init
    const filtros = ref({
      mes: window.MES_ACTUAL || new Date().getMonth() + 1,
      anio: window.ANIO_ACTUAL || new Date().getFullYear()
    });

    const formatFecha = (f) => {
      if (!f) return '';
      const parts = f.split('-');
      return `${parts[2]}/${parts[1]}/${parts[0]}`;
    };

    const listar = async () => {
      loading.value = true;
      try {
        const res = await axios.get(`${BASE}listar`, { params: filtros.value });
        if (res.data.ok) {
          registros.value = res.data.data;
        }
      } catch (e) {
        console.error("Error al listar registros Yape", e);
        Swal.fire('Error', 'Fallo de red al listar', 'error');
      } finally {
        loading.value = false;
      }
    };

    const nuevoRegistro = async () => {
      const hoy = new Date().toISOString().split('T')[0];
      const horaActual = new Date().getHours();
      // MAÑANA = 6:00 a 13:59 | TARDE = 14:00 en adelante
      const turnoSugerido = (horaActual >= 6 && horaActual < 14) ? 'MAÑANA' : 'TARDE';

      const { value: formData } = await Swal.fire({
        title: 'Nuevo Registro Yape',
        html: `
          <div style="text-align:left; font-size:14px;">
            <label style="font-weight:700; font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px;">Fecha del Gasto</label>
            <input type="date" id="swal-fecha" class="swal2-input" value="${hoy}" style="margin:6px 0 14px; width:100%;">
            <label style="font-weight:700; font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px;">Turno</label>
            <select id="swal-turno" class="swal2-select" style="margin:6px 0 0; width:100%;">
              <option value="MAÑANA" ${turnoSugerido === 'MAÑANA' ? 'selected' : ''}>☀️ Turno MAÑANA (6AM - 2PM)${turnoSugerido === 'MAÑANA' ? ' ← ahora' : ''}</option>
              <option value="TARDE"  ${turnoSugerido === 'TARDE'  ? 'selected' : ''}>🌙 Turno TARDE  (2PM - 10PM)${turnoSugerido === 'TARDE'  ? ' ← ahora' : ''}</option>
            </select>
            <div style="margin-top:10px; font-size:12px; color:#6b7280; background:#f3f4f6; border-radius:6px; padding:8px;">
              🕐 Hora actual: <b>${new Date().toLocaleTimeString('es-PE', {hour:'2-digit', minute:'2-digit'})}</b> → Turno sugerido: <b>${turnoSugerido}</b>
            </div>
          </div>`,
        showCancelButton: true,
        confirmButtonText: 'Continuar →',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
          const fecha = document.getElementById('swal-fecha').value;
          const turno = document.getElementById('swal-turno').value;
          if (!fecha) { Swal.showValidationMessage('Selecciona una fecha'); return false; }
          return { fecha, turno };
        }
      });

      if (!formData) return;
      window.location.href = `form.php?nuevo=1&turno=${formData.turno}&fecha=${formData.fecha}`;
    };

    onMounted(() => {
      listar();
    });

    return {
      loading, registros, filtros,
      formatFecha, listar, nuevoRegistro
    };
  }
}).mount('#app-yape-index');
