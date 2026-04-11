/**
 * app/Views/yape/form.js — Modo cuadrícula (categorías fijas, estilo Excel)
 */
const { createApp, ref, reactive, computed, onMounted } = Vue;

// Categorías fijas — las keys deben coincidir EXACTAMENTE con lo que se guarda en DB
const CATEGORIAS = [
  { key: 'MERCADO',        label: 'MERCADO',            icon: 'bi-basket2-fill',  color: '#16a34a' },
  { key: 'MOVILIDAD',      label: 'MOVILIDAD',          icon: 'bi-truck',         color: '#2563eb' },
  { key: 'CAFETERÍA/VEA',  label: 'CAFETERÍA / VEA',    icon: 'bi-cup-hot-fill',  color: '#d97706' },
  { key: 'LAVANDERÍA',     label: 'LAVANDERÍA',         icon: 'bi-droplet-fill',  color: '#0891b2' },
  { key: 'SERV. REPUESTOS',label: 'SERV. / REPUESTOS',  icon: 'bi-tools',         color: '#7c3aed' },
];

createApp({
  setup() {
    const BASE = '../../../api/yape.php?action=';

    const loading  = ref(true);
    const id       = ref(window.ID_REGISTRO);
    const esNuevo  = ref(window.ES_NUEVO);
    const estado   = ref('borrador');

    const fecha               = ref(window.FECHA_GET || new Date().toISOString().split('T')[0]);
    const turno               = ref(window.TURNO_GET || 'MAÑANA');
    const yape_recibido       = ref(0);
    const observacion_general = ref('');

    // Montos / refs / observaciones por categoría (reactivos)
    const montos = reactive({});
    const refs   = reactive({});
    const obs    = reactive({});

    // Inicializar todas las categorías + OTROS en cero
    const initCampos = () => {
      [...CATEGORIAS, { key: 'OTROS' }].forEach(c => {
        montos[c.key] = 0;
        refs[c.key]   = '';
        obs[c.key]    = '';
      });
    };

    // COMPUTEDS
    const totalGastado = computed(() =>
      Object.values(montos).reduce((acc, v) => acc + (parseFloat(v) || 0), 0)
    );

    const vueltoComputed = computed(() =>
      (parseFloat(yape_recibido.value) || 0) - totalGastado.value
    );

    // Convierte la cuadrícula al array de detalles que espera el API
    const buildDetalles = () => {
      const todos = [...CATEGORIAS, { key: 'OTROS', label: 'OTROS' }];
      return todos
        .filter(c => parseFloat(montos[c.key]) > 0)
        .map(c => ({
          rubro:       c.key,
          monto:       parseFloat(montos[c.key]) || 0,
          documento:   refs[c.key] || '',
          observacion: obs[c.key]  || ''
        }));
    };

    // Rellena la cuadrícula a partir de los detalles del API
    const llenarDesdeDetalles = (detalles) => {
      initCampos();
      detalles.forEach(d => {
        const key = d.rubro;
        montos[key] = parseFloat(d.monto) || 0;
        refs[key]   = d.documento  || '';
        obs[key]    = d.observacion || '';
      });
    };

    // CARGA
    const cargarDetalle = async () => {
      loading.value = true;
      initCampos();
      try {
        if (!esNuevo.value && id.value > 0) {
          const res = await axios.get(`${BASE}detalle&id=${id.value}`);
          if (res.data.ok && res.data.data) {
            const data = res.data.data;
            fecha.value               = data.fecha;
            turno.value               = data.turno;
            yape_recibido.value       = parseFloat(data.yape_recibido) || 0;
            observacion_general.value = data.observacion || '';
            estado.value              = data.estado;
            llenarDesdeDetalles(data.detalles || []);
          } else {
            Swal.fire('Error', 'Registro no encontrado', 'error').then(() => {
              window.location.href = 'index.php';
            });
          }
        }
      } catch (e) {
        Swal.fire('Error', 'Fallo de red al intentar obtener los datos', 'error');
      } finally {
        loading.value = false;
      }
    };

    const getPayload = () => ({
      id:           id.value,
      fecha:        fecha.value,
      turno:        turno.value,
      yape_recibido: yape_recibido.value,
      observacion:  observacion_general.value,
      detalles:     buildDetalles()
    });

    const validar = () => {
      if (!fecha.value) {
        Swal.fire('Aviso', 'Indica la fecha del registro.', 'warning');
        return false;
      }
      if ((parseFloat(yape_recibido.value) || 0) <= 0) {
        Swal.fire('Aviso', 'El monto de Yape recibido debe ser mayor a 0.', 'warning');
        return false;
      }
      return true;
    };

    const guardarBorrador = async (silencioso = false) => {
      if (!validar()) return false;
      try {
        if (!silencioso) Swal.showLoading();
        const res = await axios.post(`${BASE}guardar`, getPayload());
        if (!silencioso) Swal.close();
        if (res.data.ok) {
          if (!silencioso) {
            Swal.fire({ icon: 'success', title: res.data.msg, toast: true, position: 'top-end', showConfirmButton: false, timer: 1500 }).then(() => {
                window.location.href = 'index.php';
            });
          }
          return true;
        } else {
          Swal.fire('Error', res.data.msg, 'error');
          return false;
        }
      } catch (e) {
        if (!silencioso) Swal.close();
        Swal.fire('Error', e.response?.data?.msg || 'Fallo de conexión.', 'error');
        return false;
      }
    };

    const cerrarRegistro = async () => {
      if (!validar()) return;
      if (vueltoComputed.value < 0) {
        Swal.fire('Error', 'No puedes cerrar si gastaste más de lo recibido.', 'error');
        return;
      }
      const conf = await Swal.fire({
        title: '¿Confirmar cierre?',
        html: `S/ <b>${vueltoComputed.value.toFixed(2)}</b> pasarán al <b>Flujo de Caja</b> como efectivo.<br><span class="text-danger fw-bold small">Ya no podrás modificar este registro.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Sí, CERRAR',
        cancelButtonText: 'Revisar'
      });
      if (!conf.isConfirmed) return;

      const guardado = await guardarBorrador(true);
      if (!guardado) return;

      Swal.fire({ title: 'Cerrando...', didOpen: () => Swal.showLoading() });
      try {
        const res = await axios.post(`${BASE}cerrar`, { id: id.value });
        if (res.data.ok) {
          Swal.fire('¡Éxito!', res.data.msg, 'success').then(() => {
            window.location.href = 'index.php';
          });
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        Swal.fire('Error', e.response?.data?.msg || 'Fallo al cerrar.', 'error');
      }
    };

    onMounted(() => cargarDetalle());

    return {
      loading, id, esNuevo, estado,
      fecha, turno, yape_recibido, observacion_general,
      categorias: CATEGORIAS,
      montos, refs, obs,
      totalGastado, vueltoComputed,
      guardarBorrador, cerrarRegistro
    };
  }
}).mount('#app-yape-form');
