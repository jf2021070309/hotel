/**
 * app/Views/rooming/v2.js
 * Controlador de Vue 3 para Rooming V2.
 */
const { createApp } = Vue;

createApp({
  data() {
    const hoy = new Date();
    return {
      filas: [],
      busqueda: '',
      loading: false,
      filtro: {
        mes: String(hoy.getMonth() + 1),
        anio: String(hoy.getFullYear()),
        anios: [2024, 2025, 2026, 2027, 2028]
      },
      sugerencias: {},
      lookupLoading: {},
      lookupOk: {},
      searchDebounce: null,
      lookupDebounce: null
    };
  },

  computed: {
    filasFiltradas() {
      if (!this.busqueda.trim()) {
        return this.filas;
      }
      const q = this.busqueda.toLowerCase().trim();
      return this.filas.filter(f => {
        return (
          (f.nombre_apellido && f.nombre_apellido.toLowerCase().includes(q)) ||
          (f.hab && f.hab.toLowerCase().includes(q)) ||
          (f.operador && f.operador.toLowerCase().includes(q)) ||
          (f.documento_num && f.documento_num.toLowerCase().includes(q)) ||
          (f.medio_reserva && f.medio_reserva.toLowerCase().includes(q)) ||
          (f.observaciones && f.observaciones.toLowerCase().includes(q))
        );
      });
    },
    cambiosCount() {
      return this.filas.filter(f => f.modificado || !f.stay_id).length;
    }
  },

  methods: {
    async cargarDatos() {
      this.loading = true;
      try {
        const url = `${window.SERVER_DATA.apiEndpoint}?action=listar&mes=${this.filtro.mes}&anio=${this.filtro.anio}`;
        const resp = await fetch(url);
        const json = await resp.json();
        
        if (json.ok) {
          // Sanitizar y mapear modificado = false
          this.filas = json.data.map(f => ({
            ...f,
            modificado: false
          }));
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error al cargar datos',
            text: json.msg || 'Error desconocido'
          });
        }
      } catch (err) {
        console.error(err);
        Swal.fire({
          icon: 'error',
          title: 'Error de red',
          text: 'No se pudo conectar con el servidor.'
        });
      } finally {
        this.loading = false;
      }
    },

    agregarFila() {
      const hoyStr = new Date().toISOString().split('T')[0];
      const tempId = 'new_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
      
      this.filas.push({
        stay_id: null,
        pax_id: null,
        temp_id: tempId,
        operador: window.SERVER_DATA.operadorDefault,
        fecha: hoyStr,
        hab: '',
        tipo_hab: 'SIMPLE',
        pax: 1,
        medio_reserva: 'DIRECTO',
        hora_checkin: new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', hour12: false }),
        nombre_apellido: '',
        documento_tipo: 'DNI',
        documento_num: '',
        nacionalidad: 'Peruana',
        ciudad: '',
        fecha_checkin: hoyStr,
        fecha_checkout: hoyStr,
        pago_total: 0.00,
        late_checkout: 'NO',
        medio_pago: 'SOLES EFECTIVO',
        comprobante_pago: 'NINGUNO',
        numero_comprobante: '',
        quien_cobro: window.SERVER_DATA.operadorDefault,
        carro: 'NO',
        observaciones: '',
        modificado: true
      });

      // Hacer scroll al fondo de la tabla de forma suave
      setTimeout(() => {
        const container = document.querySelector('.mensual-grid-container');
        if (container) {
          container.scrollTo({
            top: container.scrollHeight,
            behavior: 'smooth'
          });
        }
      }, 100);
    },

    marcarModificado(fila) {
      fila.modificado = true;
    },

    async eliminarFila(fila, idx) {
      if (!fila.stay_id) {
        // Es una fila nueva que no ha sido guardada en BD
        this.filas.splice(idx, 1);
        return;
      }

      const result = await Swal.fire({
        title: '¿Está seguro de eliminar?',
        text: `Se eliminará de forma permanente el registro del huésped: ${fila.nombre_apellido || 'Sin nombre'}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
      });

      if (result.isConfirmed) {
        try {
          const resp = await fetch(`${window.SERVER_DATA.apiEndpoint}?action=eliminar&id=${fila.stay_id}`);
          const json = await resp.json();
          if (json.ok) {
            Swal.fire({
              icon: 'success',
              title: 'Eliminado',
              text: 'El registro ha sido eliminado exitosamente.',
              timer: 1500,
              showConfirmButton: false
            });
            this.cargarDatos();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: json.msg || 'No se pudo eliminar el registro.'
            });
          }
        } catch (err) {
          console.error(err);
          Swal.fire({
            icon: 'error',
            title: 'Error de red',
            text: 'No se pudo conectar con el servidor.'
          });
        }
      }
    },

    async guardarCambios() {
      // Filtrar filas modificadas o creadas con datos mínimos
      const aGuardar = this.filas.filter(f => f.modificado && (f.nombre_apellido || f.hab));
      
      if (aGuardar.length === 0) {
        Swal.fire({
          icon: 'info',
          title: 'Sin cambios',
          text: 'No hay filas modificadas con datos válidos para guardar.',
          timer: 2000,
          showConfirmButton: false
        });
        return;
      }

      this.loading = true;
      try {
        const resp = await fetch(`${window.SERVER_DATA.apiEndpoint}?action=guardar`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ rows: aGuardar })
        });
        const json = await resp.json();
        
        if (json.ok) {
          Swal.fire({
            icon: 'success',
            title: '¡Guardado!',
            text: json.msg || 'Los cambios se han guardado exitosamente.',
            timer: 2000,
            showConfirmButton: false
          });
          this.cargarDatos();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error al guardar',
            text: json.msg || 'Ocurrió un error en el servidor.'
          });
        }
      } catch (err) {
        console.error(err);
        Swal.fire({
          icon: 'error',
          title: 'Error de conexión',
          text: 'No se pudieron enviar los cambios al servidor.'
        });
      } finally {
        this.loading = false;
      }
    },

    // Buscar clientes en la base de datos para autocompletar
    buscarClientes(f, idx) {
      if (this.searchDebounce) clearTimeout(this.searchDebounce);
      
      const q = f.nombre_apellido;
      if (!q || q.length < 2) {
        this.sugerencias[idx] = [];
        return;
      }

      this.searchDebounce = setTimeout(async () => {
        try {
          const resp = await fetch(`${window.SERVER_DATA.clientSearchEndpoint}?action=buscar_pax&q=${encodeURIComponent(q)}`);
          const json = await resp.json();
          if (json.ok && json.data) {
            this.sugerencias[idx] = json.data;
          }
        } catch (err) {
          console.error("Error buscando clientes:", err);
        }
      }, 300);
    },

    // Búsqueda automática por número de documento (DNI lookup)
    lookupDni(f, idx) {
      if (this.lookupDebounce) clearTimeout(this.lookupDebounce);
      
      const doc = f.documento_num;
      if (!doc || doc.length < 4) {
        this.lookupLoading[idx] = false;
        this.lookupOk[idx] = false;
        return;
      }

      this.lookupLoading[idx] = true;
      this.lookupDebounce = setTimeout(async () => {
        try {
          const resp = await fetch(`${window.SERVER_DATA.clientSearchEndpoint}?action=buscar_pax&q=${encodeURIComponent(doc)}`);
          const json = await resp.json();
          if (json.ok && json.data && json.data.length > 0) {
            // Match exacto o sugerencia directa
            const exact = json.data.find(c => c.documento_num === doc) || json.data[0];
            if (exact) {
              f.nombre_apellido = exact.nombre_completo;
              f.documento_tipo = exact.documento_tipo;
              if (exact.nacionalidad) f.nacionalidad = exact.nacionalidad;
              if (exact.ciudad) f.ciudad = exact.ciudad;
              this.lookupOk[idx] = true;
            }
          } else {
            this.lookupOk[idx] = false;
          }
        } catch (err) {
          console.error("Error consultando documento:", err);
        } finally {
          this.lookupLoading[idx] = false;
        }
      }, 500);
    },

    aplicarSugerencia(f, idx, s) {
      f.nombre_apellido = s.nombre_completo;
      f.documento_tipo = s.documento_tipo;
      f.documento_num = s.documento_num;
      if (s.nacionalidad) f.nacionalidad = s.nacionalidad;
      if (s.ciudad) f.ciudad = s.ciudad;
      
      f.modificado = true;
      this.sugerencias[idx] = [];
    },

    ocultarSugerencias(idx) {
      setTimeout(() => {
        this.sugerencias[idx] = [];
      }, 250);
    },

    // Exportar tabla visible a Excel usando XLSX
    exportarExcel() {
      try {
        const table = document.querySelector('.table-mensual');
        if (!table) return;

        // Clonar la tabla para remover inputs y exportar texto limpio
        const clone = table.cloneNode(true);
        
        // Eliminar primera columna de acciones (tacho)
        clone.querySelectorAll('tr').forEach(tr => {
          if (tr.cells.length > 0) {
            tr.deleteCell(0);
          }
        });

        // Reemplazar inputs/selects por sus valores de texto correspondientes
        clone.querySelectorAll('input, select, textarea').forEach(el => {
          const td = el.parentNode;
          td.textContent = el.value || '';
        });

        const wb = XLSX.utils.table_to_book(clone, { sheet: "Rooming V2" });
        const nombreArchivo = `Rooming_V2_${this.filtro.mes}_${this.filtro.anio}.xlsx`;
        XLSX.writeFile(wb, nombreArchivo);
      } catch (err) {
        console.error(err);
        Swal.fire({
          icon: 'error',
          title: 'Error de exportación',
          text: 'No se pudo generar el archivo Excel.'
        });
      }
    }
  },

  mounted() {
    this.cargarDatos();
  }
}).mount('#app-rooming-v2');
