/**
 * app/Views/flujo/index.js
 * Vue 3 Composition API — Lista de Flujos de Caja
 */
const { createApp, ref, reactive, onMounted, onUnmounted } = Vue;

createApp({
  setup() {
    const BASE = '../../../api/flujo.php?action=';

    const loading = ref(true);
    const loadingCheck = ref(false);
    const flujos = ref([]);
    
    const today = new Date();
    const filtros = reactive({
      mes: today.getMonth() + 1,
      anio: today.getFullYear(),
      estado: 'todos'
    });

    const meses = [
      'Enero','Febrero','Marzo','Abril','Mayo','Junio',
      'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
    ];

    const mesesShort = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

    let pollingTimer = null;

    // CARGAR LISTA
    const listar = async (silent = false) => {
      if (!silent) loading.value = true;
      try {
        const res = await axios.get(`${BASE}listar&mes=${filtros.mes}&anio=${filtros.anio}&estado=${filtros.estado}`);
        if (res.data.ok) {
          flujos.value = res.data.data;
        }
      } catch (e) {
        console.error("Error al listar flujos", e);
      } finally {
        if (!silent) loading.value = false;
      }
    };

    // BADGE COLORS
    const estadoClass = (estado) => ({
      'bg-secondary': estado === 'borrador',
      'bg-primary': estado === 'cerrado',
      'bg-success': estado === 'depositado'
    });

    // NUEVO TURNO (CREACIÓN INMEDIATA)
    const nuevoTurno = async () => {
      const hora = new Date().getHours();
      const turnoSugerido = (hora >= 6 && hora < 14) ? 'MAÑANA' : 'TARDE';
      const fechaHoy = new Date().toISOString().split('T')[0];

      const { value: formValues } = await Swal.fire({
        title: 'Abrir Nuevo Turno de Caja',
        html: `
          <div style="text-align:left; margin-bottom: 16px;">
            <label style="font-weight:600; font-size:14px; color:#555; display:block; margin-bottom:6px;">
              Fecha de Apertura
            </label>
            <input type="date" id="swal-fecha" value="${fechaHoy}"
              style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:8px; font-size:15px; color:#333;">
          </div>
          <div style="display:flex; gap:16px; justify-content:center;">
            <label style="cursor:pointer; border:2px solid #ddd; border-radius:10px; padding:12px 20px; flex:1; text-align:center; transition:all .2s;" id="lbl-manana">
              <input type="radio" name="swal-turno" value="MAÑANA" ${turnoSugerido === 'MAÑANA' ? 'checked' : ''} style="display:none;">
              <div style="font-weight:700; font-size:14px;">☀️ MAÑANA</div>
              <div style="font-size:11px; color:#777;">Apertura en hora exacta</div>
            </label>
            <label style="cursor:pointer; border:2px solid #ddd; border-radius:10px; padding:12px 20px; flex:1; text-align:center; transition:all .2s;" id="lbl-tarde">
              <input type="radio" name="swal-turno" value="TARDE" ${turnoSugerido === 'TARDE' ? 'checked' : ''} style="display:none;">
              <div style="font-weight:700; font-size:14px;">🌙 TARDE</div>
              <div style="font-size:11px; color:#777;">Apertura en hora exacta</div>
            </label>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Abrir Turno Ahora',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#6c5ce7',
        didOpen: () => {
          // Estilo visual para el radio seleccionado
          const highlight = () => {
            document.getElementById('lbl-manana').style.borderColor = 
              document.querySelector('[name="swal-turno"][value="MAÑANA"]').checked ? '#6c5ce7' : '#ddd';
            document.getElementById('lbl-tarde').style.borderColor = 
              document.querySelector('[name="swal-turno"][value="TARDE"]').checked ? '#6c5ce7' : '#ddd';
          };
          highlight();
          document.querySelectorAll('[name="swal-turno"]').forEach(r => r.addEventListener('change', highlight));
          document.querySelectorAll('#lbl-manana, #lbl-tarde').forEach(lbl => {
            lbl.addEventListener('click', () => {
              lbl.querySelector('input').checked = true;
              highlight();
            });
          });
        },
        preConfirm: () => {
          const fecha = document.getElementById('swal-fecha').value;
          const turno = document.querySelector('[name="swal-turno"]:checked')?.value;
          if (!fecha) { Swal.showValidationMessage('Selecciona una fecha'); return false; }
          if (!turno)  { Swal.showValidationMessage('Selecciona un turno'); return false; }
          return { fecha, turno };
        }
      });

      if (!formValues) return;

      loadingCheck.value = true;
      try {
        const res = await axios.post(`${BASE}guardar`, {
          fecha: formValues.fecha,
          turno: formValues.turno,
          nota_entrega: '',
          ingresos: [],
          egresos: []
        });

        if (res.data.ok) {
          window.location.href = `${window.FLUJO_ROUTES.form}?id=${res.data.data.id}`;
        } else if (res.data.data && res.data.data.turno_abierto) {
          const abierto = res.data.data;
          const result = await Swal.fire({
            title: 'Atención',
            text: res.data.msg,
            icon: 'warning',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Cerrar ahora',
            denyButtonText: `Ver caja ${abierto.abierto_turno.toLowerCase()}`,
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d63031',
            denyButtonColor: '#0984e3',
            cancelButtonColor: '#636e72',
            customClass: {
              actions: 'my-actions',
              confirmButton: 'order-1',
              denyButton: 'order-2',
              cancelButton: 'order-3'
            }
          });

          if (result.isConfirmed) {
            // Clic en "Cerrar ahora"
            loadingCheck.value = true;
            try {
              const closeRes = await axios.post(`${BASE}cerrar`, { id: abierto.abierto_id });
              if (closeRes.data.ok) {
                Swal.fire('Éxito', `El turno de ${abierto.abierto_turno.toLowerCase()} se cerró correctamente.`, 'success').then(() => {
                  listar();
                });
              } else {
                Swal.fire('Error', closeRes.data.msg || 'No se pudo cerrar el turno.', 'error');
              }
            } catch (ec) {
              console.error(ec);
              Swal.fire('Error', 'Ocurrió un error al intentar cerrar el turno.', 'error');
            } finally {
              loadingCheck.value = false;
            }
          } else if (result.isDenied) {
            // Clic en "Ver caja"
            window.location.href = `${window.FLUJO_ROUTES.form}?id=${abierto.abierto_id}`;
          }
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        console.error(e);
        const msg =
          e?.response?.data?.msg ||
          'No se pudo crear el turno. Es posible que ya exista uno abierto para esta fecha.';
        Swal.fire('Error', msg, 'error');
      } finally {
        loadingCheck.value = false;
      }
    };

    onMounted(() => {
      listar();
      pollingTimer = setInterval(() => listar(true), 10000);
    });

    onUnmounted(() => {
      if (pollingTimer) clearInterval(pollingTimer);
    });

    return {
      loading, loadingCheck,
      flujos, filtros, meses, mesesShort,
      listar, estadoClass, nuevoTurno,
      FLUJO_ROUTES: window.FLUJO_ROUTES
    };
  }
}).mount('#app-flujo-index');
