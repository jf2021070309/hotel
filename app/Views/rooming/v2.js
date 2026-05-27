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
      habitaciones: window.SERVER_DATA.habitaciones || [],
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
          this.filas = json.data.map(f => {
            const names = (f.nombre_apellido || '').split('\n');
            const docTypes = (f.documento_tipo || '').split('\n');
            const docNums = (f.documento_num || '').split('\n');
            const nacs = (f.nacionalidad || '').split('\n');
            const cities = (f.ciudad || '').split('\n');

            const count = Math.max(1, parseInt(f.pax) || 1, names.length);
            const pax_list = [];
            for (let i = 0; i < count; i++) {
              pax_list.push({
                nombre_apellido: names[i] || '',
                documento_tipo: docTypes[i] || docTypes[0] || 'DNI',
                documento_num: docNums[i] || '',
                nacionalidad: nacs[i] || 'Peruana',
                ciudad: cities[i] || ''
              });
            }

            return {
              ...f,
              pax_list,
              modificado: false
            };
          });
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
        pax_ids: '',
        temp_id: tempId,
        operador: window.SERVER_DATA.operadorDefault || '',
        fecha: hoyStr,
        hab: '',
        tipo_hab: '',
        pax: 1,
        pax_list: [
          { nombre_apellido: '', documento_tipo: 'DNI', documento_num: '', nacionalidad: 'Peruana', ciudad: '' }
        ],
        medio_reserva: '',
        hora_checkin: '',
        fecha_checkin: hoyStr,
        fecha_checkout: hoyStr,
        pago_total: '',
        late_checkout: '',
        medio_pago: '',
        comprobante_pago: '',
        numero_comprobante: '',
        quien_cobro: window.SERVER_DATA.operadorDefault || '',
        carro: '',
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

    onPaxChange(fila) {
      fila.modificado = true;
      const count = parseInt(fila.pax) || 1;
      while (fila.pax_list.length < count) {
        fila.pax_list.push({
          nombre_apellido: '',
          documento_tipo: 'DNI',
          documento_num: '',
          nacionalidad: 'Peruana',
          ciudad: ''
        });
      }
      while (fila.pax_list.length > count) {
        fila.pax_list.pop();
      }
    },

    onHabChange(fila) {
      fila.modificado = true;
      if (!fila.hab) {
        fila.tipo_hab = '';
        return;
      }
      const habObj = this.habitaciones.find(h => String(h.numero) === String(fila.hab));
      if (habObj) {
        fila.tipo_hab = habObj.tipo;
      }
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
      const aGuardar = this.filas.filter(f => f.modificado && (f.pax_list.some(p => p.nombre_apellido) || f.hab));
      
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

      // Convertir el arreglo de pasajeros de vuelta a cadenas multilínea para el backend
      const payload = aGuardar.map(f => {
        return {
          ...f,
          nombre_apellido: f.pax_list.map(p => p.nombre_apellido || '').join('\n'),
          documento_tipo: f.pax_list.map(p => p.documento_tipo || '').join('\n'),
          documento_num: f.pax_list.map(p => p.documento_num || '').join('\n'),
          nacionalidad: f.pax_list.map(p => p.nacionalidad || '').join('\n'),
          ciudad: f.pax_list.map(p => p.ciudad || '').join('\n')
        };
      });

      this.loading = true;
      try {
        const resp = await fetch(`${window.SERVER_DATA.apiEndpoint}?action=guardar`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ rows: payload })
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

    // Buscar clientes en la base de datos para autocompletar de un pax específico
    buscarClientes(f, idx, pIdx) {
      if (this.searchDebounce) clearTimeout(this.searchDebounce);
      
      const p = f.pax_list[pIdx];
      const q = p ? p.nombre_apellido : '';
      if (!q || q.length < 2) {
        this.sugerencias[idx + '_' + pIdx] = [];
        return;
      }

      this.searchDebounce = setTimeout(async () => {
        try {
          const resp = await fetch(`${window.SERVER_DATA.clientSearchEndpoint}?action=buscar_pax&q=${encodeURIComponent(q)}`);
          const json = await resp.json();
          if (json.ok && json.data) {
            this.sugerencias[idx + '_' + pIdx] = json.data;
          }
        } catch (err) {
          console.error("Error buscando clientes:", err);
        }
      }, 300);
    },

    // Búsqueda automática por número de documento (DNI lookup) de un pax específico
    lookupDni(f, idx, pIdx) {
      if (this.lookupDebounce) clearTimeout(this.lookupDebounce);
      
      const p = f.pax_list[pIdx];
      const doc = p ? p.documento_num : '';
      const key = idx + '_' + pIdx;
      if (!doc || doc.length < 4) {
        this.lookupLoading[key] = false;
        this.lookupOk[key] = false;
        return;
      }

      this.lookupLoading[key] = true;
      this.lookupDebounce = setTimeout(async () => {
        try {
          const resp = await fetch(`${window.SERVER_DATA.clientSearchEndpoint}?action=buscar_pax&q=${encodeURIComponent(doc)}`);
          const json = await resp.json();
          if (json.ok && json.data && json.data.length > 0) {
            // Match exacto o sugerencia directa
            const exact = json.data.find(c => c.documento_num === doc) || json.data[0];
            if (exact) {
              p.nombre_apellido = exact.nombre_completo;
              p.documento_tipo = exact.documento_tipo;
              if (exact.nacionalidad) p.nacionalidad = exact.nacionalidad;
              if (exact.ciudad) p.ciudad = exact.ciudad;
              this.lookupOk[key] = true;
            }
          } else {
            this.lookupOk[key] = false;
          }
        } catch (err) {
          console.error("Error consultando documento:", err);
        } finally {
          this.lookupLoading[key] = false;
        }
      }, 500);
    },

    aplicarSugerencia(f, idx, pIdx, s) {
      const p = f.pax_list[pIdx];
      if (p) {
        p.nombre_apellido = s.nombre_completo;
        p.documento_tipo = s.documento_tipo;
        p.documento_num = s.documento_num;
        if (s.nacionalidad) p.nacionalidad = s.nacionalidad;
        if (s.ciudad) p.ciudad = s.ciudad;
      }
      
      f.modificado = true;
      this.sugerencias[idx + '_' + pIdx] = [];
    },

    ocultarSugerencias(idx, pIdx) {
      setTimeout(() => {
        this.sugerencias[idx + '_' + pIdx] = [];
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
