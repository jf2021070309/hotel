/**
 * app/Views/clientes/v2.js
 * Controlador Vue 3 para la grilla plana de Clientes V2
 */
const { createApp, ref, computed, onMounted, watch } = Vue;

createApp({
  setup() {
    const loading = ref(false);
    const filas = ref([]);
    const busqueda = ref('');
    const lookupLoading = ref({});
    const lookupRucLoading = ref({});

    // API
    const api = window.SERVER_DATA.apiEndpoint;

    // Cargar datos
    const cargarDatos = async () => {
      loading.value = true;
      try {
        const res = await fetch(`${api}?action=listar`);
        const json = await res.json();
        if (json.ok) {
          filas.value = json.data.map(f => ({
            ...f,
            modificado: false
          }));
        } else {
          Swal.fire('Error', json.msg || 'No se pudieron cargar los datos', 'error');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Problema de conexión al cargar datos', 'error');
      } finally {
        loading.value = false;
      }
    };

    // Filtro local
    const filasFiltradas = computed(() => {
      if (!busqueda.value) return filas.value;
      const term = busqueda.value.toLowerCase();
      return filas.value.filter(f => {
        return (
          (f.nombre || '').toLowerCase().includes(term) ||
          (f.dni || '').toLowerCase().includes(term) ||
          (f.ruc || '').toLowerCase().includes(term) ||
          (f.empresa || '').toLowerCase().includes(term) ||
          (f.celular || '').toLowerCase().includes(term) ||
          (f.email || '').toLowerCase().includes(term) ||
          (f.nacionalidad || '').toLowerCase().includes(term) ||
          (f.ciudad || '').toLowerCase().includes(term)
        );
      });
    });

    const cambiosCount = computed(() => filas.value.filter(f => f.modificado || !f.id).length);

    // Marcar como modificado
    const marcarModificado = (fila) => {
      if (fila.id) {
        fila.modificado = true;
      }
    };

    // Añadir fila nueva
    const agregarFila = () => {
      filas.value.push({
        temp_id: 'new_' + Date.now(),
        id: null,
        nombre: '',
        dni: '',
        ruc: '',
        empresa: '',
        celular: '',
        email: '',
        nacionalidad: '',
        ciudad: '',
        modificado: true
      });
      // Hacer scroll hacia abajo para asegurar que la nueva fila es visible
      setTimeout(() => {
        const container = document.querySelector('.mensual-grid-container');
        if (container) container.scrollTop = container.scrollHeight;
      }, 50);
    };

    // Eliminar fila (local o BD)
    const eliminarFila = async (fila, idx) => {
      if (!fila.id) {
        // Es local, solo quitarla
        const realIdx = filas.value.indexOf(fila);
        if (realIdx > -1) filas.value.splice(realIdx, 1);
        return;
      }

      const conf = await Swal.fire({
        title: '¿Eliminar cliente?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar'
      });

      if (conf.isConfirmed) {
        try {
          const res = await fetch(`${api}?action=eliminar&id=${fila.id}`);
          const json = await res.json();
          if (json.ok) {
            const realIdx = filas.value.indexOf(fila);
            if (realIdx > -1) filas.value.splice(realIdx, 1);
            
            Swal.fire({
              toast: true, position: 'top-end', showConfirmButton: false, 
              timer: 3000, icon: 'success', title: 'Cliente eliminado'
            });
          } else {
            Swal.fire('Error', json.msg || 'No se pudo eliminar', 'error');
          }
        } catch (err) {
          console.error(err);
          Swal.fire('Error', 'Error de red', 'error');
        }
      }
    };

    // Guardar cambios
    const guardarCambios = async () => {
      const modificados = filas.value.filter(f => f.modificado || !f.id);
      if (modificados.length === 0) return;

      loading.value = true;
      try {
        const payload = { rows: modificados };
        const res = await fetch(`${api}?action=guardar`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (json.ok) {
          Swal.fire({
            toast: true, position: 'top-end', showConfirmButton: false, 
            timer: 3000, icon: 'success', title: json.msg || 'Cambios guardados'
          });
          cargarDatos();
        } else {
          Swal.fire('Error', json.msg || 'Error al guardar', 'error');
          loading.value = false;
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Error de red al guardar', 'error');
        loading.value = false;
      }
    };

    const exportarExcel = () => {
      const data = filasFiltradas.value.map(f => ({
        'NOMBRE': f.nombre || '',
        'DNI': f.dni || '',
        'NACIONALIDAD': f.nacionalidad || '',
        'CIUDAD': f.ciudad || '',
        'CELULAR': f.celular || '',
        'EMAIL': f.email || '',
        'RUC': f.ruc || '',
        'EMPRESA': f.empresa || ''
      }));

      const wb = XLSX.utils.book_new();
      const ws = XLSX.utils.json_to_sheet(data);
      
      // Anchos de columna
      ws['!cols'] = [
        { wch: 35 }, // NOMBRE
        { wch: 15 }, // DNI
        { wch: 20 }, // NACIONALIDAD
        { wch: 20 }, // CIUDAD
        { wch: 15 }, // CELULAR
        { wch: 30 }, // EMAIL
        { wch: 15 }, // RUC
        { wch: 35 }  // EMPRESA
      ];

      XLSX.utils.book_append_sheet(wb, ws, "Clientes Frecuentes");
      XLSX.writeFile(wb, `ClientesFrecuentes_${new Date().toISOString().split('T')[0]}.xlsx`);
    };

    // API Peru: Consultar DNI a través del Backend
    const lookupDni = async (fila, idx) => {
      if (fila.dni && fila.dni.length === 8) {
        lookupLoading.value[idx] = true;
        try {
          const res = await fetch(`${api}?action=lookup_dni&dni=${fila.dni}`);
          const json = await res.json();
          if (json.ok && json.data && json.data.nombre_completo) {
            fila.nombre = json.data.nombre_completo.toUpperCase();
            marcarModificado(fila);
          }
        } catch (e) {
          console.error('Error lookup DNI', e);
        } finally {
          lookupLoading.value[idx] = false;
        }
      }
    };

    // API Peru: Consultar RUC a través del Backend
    const lookupRuc = async (fila, idx) => {
      if (fila.ruc && fila.ruc.length === 11) {
        lookupRucLoading.value[idx] = true;
        try {
          const res = await fetch(`${api}?action=lookup_ruc&ruc=${fila.ruc}`);
          const json = await res.json();
          if (json.ok && json.data && json.data.razon_social) {
            fila.empresa = json.data.razon_social.toUpperCase();
            marcarModificado(fila);
          }
        } catch (e) {
          console.error('Error lookup RUC', e);
        } finally {
          lookupRucLoading.value[idx] = false;
        }
      }
    };

    const crearEstadiaRapida = (c) => {
      const data = {
          dni: c.dni,
          nombre: c.nombre,
          celular: c.celular,
          email: c.email,
          nacionalidad: c.nacionalidad,
          ciudad: c.ciudad,
          ruc: c.ruc,
          empresa: c.empresa,
          tipo_doc: c.dni && c.dni.length === 11 ? 'RUC' : 'DNI',
          frecuente: true
      };
      localStorage.setItem('quick_checkin_v2_pax', JSON.stringify(data));
      window.location.href = '../rooming/v2.php';
    };

    const crearReservaRapida = (c) => {
      const data = {
          dni: c.dni,
          nombre: c.nombre,
          celular: c.celular,
          email: c.email,
          nacionalidad: c.nacionalidad,
          ciudad: c.ciudad,
          tipo_doc: c.dni && c.dni.length === 11 ? 'RUC' : 'DNI',
          frecuente: true
      };
      localStorage.setItem('quick_reserva_pax', JSON.stringify(data));
      window.location.href = '../reservas/index.php';
    };

    onMounted(() => {
      cargarDatos();

      setTimeout(() => {
        const slider = document.querySelector('.mensual-grid-container');
        if (slider) {
          let isDown = false;
          let startX, startY, scrollLeft, scrollTop;

          slider.addEventListener('mousedown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'BUTTON' || e.target.closest('button') || e.target.closest('a')) {
              return;
            }
            isDown = true;
            startX = e.pageX - slider.offsetLeft;
            startY = e.pageY - slider.offsetTop;
            scrollLeft = slider.scrollLeft;
            scrollTop = slider.scrollTop;
            slider.style.cursor = 'grabbing';
            document.body.style.userSelect = 'none';
          });

          window.addEventListener('mouseup', () => {
            if (isDown) {
              isDown = false;
              slider.style.cursor = '';
              document.body.style.userSelect = '';
            }
          });

          slider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const y = e.pageY - slider.offsetTop;
            const walkX = (x - startX) * 1.5;
            const walkY = (y - startY) * 1.5;
            slider.scrollLeft = scrollLeft - walkX;
            slider.scrollTop = scrollTop - walkY;
          });
        }
      }, 300);
    });

    return {
      loading,
      busqueda,
      filas,
      filasFiltradas,
      cambiosCount,
      lookupLoading,
      lookupRucLoading,
      cargarDatos,
      agregarFila,
      eliminarFila,
      guardarCambios,
      marcarModificado,
      exportarExcel,
      lookupDni,
      lookupRuc,
      crearEstadiaRapida,
      crearReservaRapida
    };
  }
}).mount('#app-clientes-v2');
