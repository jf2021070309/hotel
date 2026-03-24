/**
 * app/Views/caja_chica/detalle.js
 * Vue 3 Composition API — Detalle Ciclo Activo
 */
const { createApp, ref, reactive, computed, onMounted } = Vue;

createApp({
  setup() {
    const BASE = '../../../api/caja_chica.php?action=';

    const loading = ref(true);
    const guardandoGasto = ref(false);
    
    const ciclo = ref(null);
    const categorias = ref([]);

    const formg = reactive({
      rubro: '',
      monto: '',
      documento: '',
      observacion: ''
    });

    const loadData = async () => {
      loading.value = true;
      try {
        const [catRes, actRes] = await Promise.all([
          axios.get(`${BASE}categorias`),
          axios.get(`${BASE}ciclo_activo`)
        ]);
        
        if (catRes.data.ok) categorias.value = catRes.data.data;
        if (actRes.data.ok) ciclo.value = actRes.data.data; // data can be null if no cycle

      } catch (e) {
        console.error(e);
      } finally {
        loading.value = false;
      }
    };

    const porcentaje_gastado = computed(() => {
      if (!ciclo.value) return 0;
      let init = parseFloat(ciclo.value.saldo_inicial);
      let gast = parseFloat(ciclo.value.total_gastado);
      if (init === 0) return 0;
      let p = (gast / init) * 100;
      return p > 100 ? 100 : p;
    });

    const registrarGasto = async () => {
      if (!formg.rubro || formg.monto <= 0) return;
      
      if (parseFloat(formg.monto) > parseFloat(ciclo.value.saldo_actual)) {
        Swal.fire('Atención', 'El gasto es mayor al saldo actual disponible en la caja.', 'warning');
        return;
      }

      guardandoGasto.value = true;
      try {
        const res = await axios.post(`${BASE}gasto`, {
          caja_id: ciclo.value.id,
          ...formg
        });

        if (res.data.ok) {
          formg.monto = '';
          formg.documento = '';
          formg.observacion = '';
          loadData(); // refresh active
          const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
          Toast.fire({ icon: 'success', title: 'Gasto guardado' });
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        Swal.fire('Error', 'No se conectó al servidor', 'error');
      } finally {
        guardandoGasto.value = false;
      }
    };

    const anularGasto = async (mov) => {
      const { value: motivo } = await Swal.fire({
        title: 'Anular Movimiento',
        input: 'text',
        inputLabel: 'Indique el motivo de la anulación',
        inputPlaceholder: 'Ingreso erróneo, recibo cancelado...',
        showCancelButton: true,
        inputValidator: (value) => {
          if (!value) return '¡El motivo es obligatorio!'
        }
      });

      if (motivo) {
        try {
          const res = await axios.post(`${BASE}anular`, {
            mov_id: mov.id,
            motivo: motivo
          });
          if (res.data.ok) {
            Swal.fire('Anulado', res.data.msg, 'success');
            loadData();
          } else {
            Swal.fire('Error', res.data.msg, 'error');
          }
        } catch(e) {
          Swal.fire('Error', 'Fallo de red', 'error');
        }
      }
    };

    const cerrarCiclo = async () => {
      const confirm = await Swal.fire({
        title: 'Cerrar Ciclo de Caja Chica',
        html: `El saldo final será de <b>S/ ${parseFloat(ciclo.value.saldo_actual).toFixed(2)}</b>.<br><br>` + 
              `¿Desea reponer los S/ 100 automáticamente del sobre de efectivo del <b class="text-primary">Flujo de Caja Activo</b>?`,
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonColor: '#3085d6',
        denyButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Cerrar y Reponer S/100',
        denyButtonText: 'Cerrar sin reponer',
        cancelButtonText: 'Cancelar'
      });

      if (confirm.isDismissed) return;

      const reponer = confirm.isConfirmed; // if they clicked "Yes" (confirm), we replace. 

      try {
        const res = await axios.post(`${BASE}cerrar`, {
          caja_id: ciclo.value.id,
          reponer: reponer
        });

        if (res.data.ok) {
          Swal.fire({
            title: '¡Caja Cerrada!',
            text: res.data.msg,
            icon: 'success'
          }).then(() => {
            window.location.href = 'index.php'; // return to history
          });
        } else {
          Swal.fire('Error', res.data.msg, 'error');
        }
      } catch (e) {
        Swal.fire('Error de red', '', 'error');
      }
    };

    onMounted(() => {
      loadData();
    });

    return {
      loading, guardandoGasto, ciclo, categorias, formg, porcentaje_gastado,
      registrarGasto, anularGasto, cerrarCiclo
    };
  }
}).mount('#app-cchica-detalle');
