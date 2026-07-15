/**
 * app/Views/yape/index.js
 */
const { createApp, ref, onMounted, onUnmounted } = Vue;

createApp({
  setup() {
    const BASE = '../../../api/yape.php?action=';

    const loading = ref(true);
    const registros = ref([]);
    const dataListaOriginal = ref([]);
    const diasAgrupados = ref([]);
    const globales = ref({
       yape_recibido: 0, total_gastado: 0, vuelto: 0,
       rubros: { 'MERCADO': 0, 'MOVILIDAD': 0, 'CAFETERÍA/VEA': 0, 'LAVANDERÍA': 0, 'SERV. REPUESTOS': 0, 'OTROS': 0 }
    });
    
    // Config filtros init
    const filtros = ref({
      mes: window.MES_ACTUAL || new Date().getMonth() + 1,
      anio: window.ANIO_ACTUAL || new Date().getFullYear(),
      turno: '',
      estado: ''
    });

    const categoriasConfig = ['MERCADO', 'MOVILIDAD', 'CAFETERÍA/VEA', 'LAVANDERÍA', 'SERV. REPUESTOS', 'OTROS'];

    const formatFecha = (f) => {
      if (!f) return '';
      const parts = f.split('-');
      return `${parts[2]}/${parts[1]}/${parts[0]}`;
    };

    const aplicarFiltrosFront = () => {
        let filtrados = dataListaOriginal.value;

        if (filtros.value.turno) {
            filtrados = filtrados.filter(r => r.turno === filtros.value.turno);
        }
        if (filtros.value.estado) {
            filtrados = filtrados.filter(r => r.estado === filtros.value.estado);
        }

        registros.value = filtrados;

        // Agrupar por fecha y sumar globales
        const grupos = {};
        globales.value = {
            yape_recibido: 0, total_gastado: 0, vuelto: 0,
            rubros: { 'MERCADO': 0, 'MOVILIDAD': 0, 'CAFETERÍA/VEA': 0, 'LAVANDERÍA': 0, 'SERV. REPUESTOS': 0, 'OTROS': 0 }
        };

        filtrados.forEach(r => {
             if(!grupos[r.fecha]) {
                grupos[r.fecha] = {
                   fecha: r.fecha,
                   turnos: [],
                   totales: {
                      yape_recibido: 0, total_gastado: 0, vuelto: 0,
                      rubros: { 'MERCADO': 0, 'MOVILIDAD': 0, 'CAFETERÍA/VEA': 0, 'LAVANDERÍA': 0, 'SERV. REPUESTOS': 0, 'OTROS': 0 }
                   }
                };
             }
             grupos[r.fecha].turnos.push(r);
             
             // Sumar locales
             grupos[r.fecha].totales.yape_recibido += parseFloat(r.yape_recibido) || 0;
             grupos[r.fecha].totales.total_gastado += parseFloat(r.total_gastado) || 0;
             grupos[r.fecha].totales.vuelto += parseFloat(r.vuelto) || 0;
             
             // Sumar globales
             globales.value.yape_recibido += parseFloat(r.yape_recibido) || 0;
             globales.value.total_gastado += parseFloat(r.total_gastado) || 0;
             globales.value.vuelto += parseFloat(r.vuelto) || 0;

             // Sumar rubros
             if (r.detalles_montos) {
                 for(let key in grupos[r.fecha].totales.rubros) {
                     let monto = parseFloat(r.detalles_montos[key]) || 0;
                     grupos[r.fecha].totales.rubros[key] += monto;
                     globales.value.rubros[key] += monto;
                 }
             }
        });
        
        diasAgrupados.value = Object.values(grupos).sort((a, b) => b.fecha.localeCompare(a.fecha));
    };

    let pollingTimer = null;

    const listar = async (silent = false) => {
      if (!silent) loading.value = true;
      try {
        const res = await axios.get(`${BASE}listar`, { params: { mes: filtros.value.mes, anio: filtros.value.anio } });
        if (res.data.ok) {
          dataListaOriginal.value = res.data.data;
          aplicarFiltrosFront();
        } else if (!silent) {
          Swal.fire('Error', res.data?.msg || 'No se pudo listar los registros Yape.', 'error');
        }
      } catch (e) {
        console.error("Error al listar registros Yape", e);
        const msg = e.response?.data?.msg || e.message || 'Fallo de red al listar';
        Swal.fire('Error', msg, 'error');
      } finally {
        if (!silent) loading.value = false;
      }
    };

    const nuevoRegistroForm = (fechaDef, turnoDef) => {
        window.location.href = `form.php?nuevo=1&turno=${turnoDef}&fecha=${fechaDef}`;
    };

    const nuevoRegistro = async () => {
      const hoy = new Date().toISOString().split('T')[0];

      const { value: formData } = await Swal.fire({
        title: 'Inicializar Día Yape',
        html: `
          <div style="text-align:left; font-size:14px;">
            <label style="font-weight:700; font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px;">Fecha</label>
            <input type="date" id="swal-fecha" class="swal2-input" value="${hoy}" style="margin:6px 0 14px; width:100%;">
            <label style="font-weight:700; font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px;">Monto Inicial (S/)</label>
            <input type="number" id="swal-monto" class="swal2-input" placeholder="0.00" step="0.01" min="0" value="0.00" style="margin:6px 0 0; width:100%;">
            <div style="margin-top:14px; font-size:12px; color:#6b7280; background:#f3f4f6; border-radius:6px; padding:10px;">
              <i class="bi bi-info-circle text-primary me-1"></i> Se crearán automáticamente los turnos <b>MAÑANA</b> y <b>TARDE</b>.
            </div>
          </div>`,
        showCancelButton: true,
        confirmButtonText: 'Crear Día →',
        confirmButtonColor: '#198754',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
          const fecha = document.getElementById('swal-fecha').value;
          const monto = document.getElementById('swal-monto').value;
          if (!fecha) { Swal.showValidationMessage('Selecciona una fecha'); return false; }
          return { fecha, yape_recibido: parseFloat(monto) || 0 };
        }
      });

      if (!formData) return;

      Swal.fire({ title: 'Creando turnos...', didOpen: () => Swal.showLoading() });
      try {
          const res = await axios.post(`${BASE}crear_dia`, formData);
          if (res.data.ok) {
              Swal.fire({ icon: 'success', title: 'Día inicializado correctamente', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
              listar();
          } else {
              Swal.fire('Error', res.data?.msg || 'No se pudo crear el día Yape.', 'error');
          }
      } catch (e) {
          const msg = e.response?.data?.msg || e.message || 'Fallo de conexión.';
          Swal.fire('Error', msg, 'error');
      }
    };

    const abrirModalCelda = async (yapeRow, campo) => {
        let title = campo === 'YAPE_RECIBIDO' ? 'YAPE RECIBIDO' : campo;
        let currentMonto = '';
        let currentNota = '';

        if (campo === 'YAPE_RECIBIDO') {
            currentMonto = yapeRow.yape_recibido > 0 ? parseFloat(yapeRow.yape_recibido).toFixed(2) : '';
            currentNota = yapeRow.observacion || '';
        } else {
            if (yapeRow.detalles_montos && yapeRow.detalles_montos[campo] > 0) {
                currentMonto = parseFloat(yapeRow.detalles_montos[campo]).toFixed(2);
            }
            if (yapeRow.detalles_info && yapeRow.detalles_info[campo] && yapeRow.detalles_info[campo].observacion) {
                currentNota = yapeRow.detalles_info[campo].observacion;
            }
        }

        const { value: formData } = await Swal.fire({
            title: `Editar: ${title}`,
            html: `
                <div style="text-align: left; font-size: 14px;">
                    <label style="font-weight:700; font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px;">Monto (S/)</label>
                    <input type="number" id="swal-celda-monto" class="swal2-input" placeholder="0.00" step="0.01" min="0" value="${currentMonto}" style="margin:6px 0 14px; width:100%;">
                    
                    <label style="font-weight:700; font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px;">Nota / Observación</label>
                    <textarea id="swal-celda-nota" class="swal2-textarea" placeholder="Opcional..." style="margin:6px 0 0; width:100%; height: 80px;">${currentNota}</textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            confirmButtonColor: '#0f172a',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                return {
                    id: yapeRow.id,
                    campo: campo,
                    monto: document.getElementById('swal-celda-monto').value,
                    nota: document.getElementById('swal-celda-nota').value
                };
            }
        });

        if (formData) {
            try {
                Swal.fire({ title: 'Guardando...', didOpen: () => Swal.showLoading() });
                const res = await axios.post(`${BASE}guardar_celda`, formData);
                if (res.data.ok) {
                    Swal.fire({ icon: 'success', title: 'Guardado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    listar(true); // reload silently
                } else {
                    Swal.fire('Error', res.data.msg || 'No se pudo guardar la celda', 'error');
                }
            } catch (e) {
                Swal.fire('Error', e.response?.data?.msg || e.message || 'Error de red', 'error');
            }
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
      globales, loading, registros, diasAgrupados, filtros, categoriasConfig,
      formatFecha, listar, aplicarFiltrosFront, nuevoRegistro, nuevoRegistroForm, abrirModalCelda
    };
  }
}).mount('#app-yape-index');
