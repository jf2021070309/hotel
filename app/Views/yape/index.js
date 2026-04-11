/**
 * app/Views/yape/index.js
 */
const { createApp, ref, onMounted } = Vue;

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

    const listar = async () => {
      loading.value = true;
      try {
        const res = await axios.get(`${BASE}listar`, { params: { mes: filtros.value.mes, anio: filtros.value.anio } });
        if (res.data.ok) {
          dataListaOriginal.value = res.data.data;
          aplicarFiltrosFront();
        }
      } catch (e) {
        console.error("Error al listar registros Yape", e);
        Swal.fire('Error', 'Fallo de red al listar', 'error');
      } finally {
        loading.value = false;
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
              Swal.fire('Error', res.data.msg, 'error');
          }
      } catch (e) {
          Swal.fire('Error', e.response?.data?.msg || 'Fallo de conexión.', 'error');
      }
    };

    const verNota = (rubro, info) => {
        let html = '<div style="text-align: left; font-size: 14px; color: #334155;">';
        if (info.observacion) {
            html += `<div style="margin-bottom:12px;"><label style="font-size:11px; font-weight:bold; color:#64748b; text-transform:uppercase;">Nota / Observación:</label><div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:10px; margin-top:4px;">${info.observacion}</div></div>`;
        }
        if (info.documento) {
            html += `<div><label style="font-size:11px; font-weight:bold; color:#64748b; text-transform:uppercase;">N° Documento:</label><div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:10px; margin-top:4px; font-family: monospace;">${info.documento}</div></div>`;
        }
        html += '</div>';

        Swal.fire({
            title: `Detalle: ${rubro}`,
            html: html,
            icon: 'info',
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#0f172a'
        });
    };

    onMounted(() => {
      listar();
    });

    return {
      globales, loading, registros, diasAgrupados, filtros, categoriasConfig,
      formatFecha, listar, aplicarFiltrosFront, nuevoRegistro, nuevoRegistroForm, verNota
    };
  }
}).mount('#app-yape-index');
