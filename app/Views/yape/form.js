/**
 * app/Views/yape/form.js
 */
const { createApp, ref, computed, onMounted } = Vue;

createApp({
  setup() {
    const BASE = '../../../api/yape.php?action=';

    const loading = ref(true);
    
    // States
    const id = ref(window.ID_REGISTRO);
    const esNuevo = ref(window.ES_NUEVO);
    const estado = ref('borrador');

    // Headers
    const fecha = ref('');
    const turno = ref(window.TURNO_GET || 'MAÑANA');
    const yape_recibido = ref(0);
    const observacion_general = ref('');

    // Table rows
    const detalles = ref([]);

    // Logic
    const agregarFila = () => {
      detalles.value.push({
        rubro: '',
        monto: null,
        documento: '',
        observacion: ''
      });
    };

    const eliminarFila = (idx) => {
      detalles.value.splice(idx, 1);
    };

    // Computeds
    const totalGastado = computed(() => {
      return detalles.value.reduce((acc, current) => {
        let m = parseFloat(current.monto) || 0;
        return acc + m;
      }, 0);
    });

    const vueltoComputed = computed(() => {
      let r = parseFloat(yape_recibido.value) || 0;
      return r - totalGastado.value;
    });

    const alertaVuelto = computed(() => {
      let v = vueltoComputed.value;
      if (v < 0) {
        return {
          class: 'alert-danger',
          label: 'FALTANTE (ALERTA)',
          msg: '<i class="bi bi-exclamation-triangle-fill"></i> Has gastado más de lo recibido. Revisa los montos.'
        };
      } else if (v === 0) {
        return {
          class: 'alert-secondary',
          label: 'VUELTO',
          msg: 'Dinero exacto gastado.'
        };
      } else {
        return {
          class: 'alert-success',
          label: 'VUELTO A EFECTIVO',
          msg: '<i class="bi bi-arrow-right-circle-fill"></i> Este sobrante se inyectará al <b>Flujo de Caja</b> al Cerrar.'
        };
      }
    });

    // Loading existing data
    const cargarDetalle = async () => {
      loading.value = true;
      try {
        if (!esNuevo.value && id.value > 0) {
          const res = await axios.get(`${BASE}detalle&id=${id.value}`);
          if (res.data.ok && res.data.data) {
            let data = res.data.data;
            fecha.value = data.fecha;
            turno.value = data.turno;
            yape_recibido.value = parseFloat(data.yape_recibido);
            observacion_general.value = data.observacion;
            estado.value = data.estado;

            if (data.detalles && data.detalles.length > 0) {
              detalles.value = data.detalles.map(d => ({
                rubro: d.rubro,
                monto: parseFloat(d.monto),
                documento: d.documento,
                observacion: d.observacion
              }));
            }
          } else {
            Swal.fire('Error', 'Registro no encontrado', 'error').then(() => {
              window.location.href = 'index.php';
            });
          }
        } else {
          // Defaults if new
          const d = new Date();
          fecha.value = d.toISOString().split('T')[0];
          agregarFila(); // start with one blank
        }
      } catch (e) {
        console.error("Error al cargar yape", e);
        Swal.fire('Error', 'Fallo de red al intentar obtener los datos', 'error');
      } finally {
        loading.value = false;
      }
    };

    const getPayload = () => {
      return {
        id: id.value,
        fecha: fecha.value,
        turno: turno.value,
        yape_recibido: yape_recibido.value,
        observacion: observacion_general.value,
        detalles: detalles.value
      };
    };

    const validar = () => {
      if (yape_recibido.value <= 0) {
        Swal.fire('Aviso', 'El monto de Yape recibido debe ser mayor a 0.', 'warning');
        return false;
      }
      for (let i=0; i < detalles.value.length; i++) {
        let d = detalles.value[i];
        if (!d.rubro && d.monto > 0) {
          Swal.fire('Aviso', `La fila ${i+1} tiene un monto pero no se indicó el rubro.`, 'warning');
          return false;
        }
        if (d.rubro && (isNaN(d.monto) || d.monto <= 0)) {
          Swal.fire('Aviso', `La fila ${i+1} (${d.rubro}) tiene un monto inválido.`, 'warning');
          return false;
        }
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
             Swal.fire({
              icon: 'success',
              title: res.data.msg,
              toast: true,
              position: 'top-end',
              showConfirmButton: false,
              timer: 3000
             });
          }
          if (esNuevo.value) {
            esNuevo.value = false;
            id.value = res.data.data.id;
            // update url silently
            window.history.replaceState({}, '', `form.php?id=${id.value}`);
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
        Swal.fire('Error Crítico', 'No puedes cerrar la rendición si has gastado más de lo que recibiste. ¡Corrige los datos!', 'error');
        return;
      }

      const conf = await Swal.fire({
        title: '¿Confirmas cerrar este registro?',
        html: `Al hacerlo, <b>S/ ${vueltoComputed.value.toFixed(2)}</b> pasarán inmediatamente a la suma del <b>Flujo de Caja</b> como Ingreso de Tu Turno Actual en EFECTIVO.<br><br><span class="text-danger fw-bold">Ya no podrás modificar esta rendición.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, CERRAR Y RENDIR CUENTAS',
        cancelButtonText: 'Revisar más'
      });

      if (conf.isConfirmed) {
        // Primer paso: Guardar para asegurar q todo se manda fresco
        const guardado = await guardarBorrador(true);
        if (!guardado) return;

        Swal.fire({ title: 'Cerrando y Tranfiriendo...', didOpen: () => { Swal.showLoading() }});
        
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
          Swal.fire('Error al Cerrar', e.response?.data?.msg || 'Fallo al realizar la transferencia.', 'error');
        }
      }
    };

    onMounted(() => {
      cargarDetalle();
    });

    return {
      loading, id, esNuevo, estado,
      fecha, turno, yape_recibido, observacion_general, 
      detalles, agregarFila, eliminarFila,
      totalGastado, vueltoComputed, alertaVuelto,
      guardarBorrador, cerrarRegistro
    };
  }
}).mount('#app-yape-form');
