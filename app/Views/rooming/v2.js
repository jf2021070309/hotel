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
      lookupDebounce: null,

      // ── REPORTE PAX ────────────────────────────────────────────
      reportePax: {
        cargando: false,
        mes: String(new Date().getMonth() + 1),
        anio: String(new Date().getFullYear()),
        anios: [2024, 2025, 2026, 2027, 2028],
        filas: []
      },
      selColumnas: [
        { label: 'OPERADOR',          checked: true },
        { label: 'FECHA REGISTRO',    checked: true },
        { label: 'HAB',               checked: true },
        { label: 'TIPO DE HAB',       checked: true },
        { label: 'PAX',               checked: true },
        { label: 'MEDIO DE RESERVA',  checked: true },
        { label: 'HORA DE CHECK IN',  checked: true },
        { label: 'NOMBRE Y APELLIDO', checked: true },
        { label: 'TIPO DOC',          checked: true },
        { label: 'NÚMERO',            checked: true },
        { label: 'NACIONALIDAD',      checked: true },
        { label: 'CIUDAD',            checked: true },
        { label: 'ENTRADA',           checked: true },
        { label: 'SALIDA',            checked: true },
        { label: 'PAGO TOTAL',        checked: true },
        { label: 'LATE',              checked: true },
        { label: 'METODO',            checked: true },
        { label: 'COMPROBANTE',       checked: true },
        { label: 'Nº COMPROBANTE',    checked: true },
        { label: 'QUIEN COBRO',       checked: true },
        { label: 'CARRO',             checked: true },
        { label: 'OBS',              checked: true },
      ]
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
          // Extraer datos (soporta nuevo formato con filas y habitaciones, o el formato antiguo)
          let rawData = json.data;
          if (json.data && json.data.filas !== undefined) {
            rawData = json.data.filas;
            if (json.data.habitaciones) {
              this.habitaciones = json.data.habitaciones;
            }
          }
          
          // Sanitizar y mapear modificado = false
          this.filas = rawData.map(f => {
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

            const periodos_list = [];
            // Build periodos from historial + current checkout
            const histDates = (f.fechas_checkout_historial || '').split('\n').filter(d => d);
            const allCheckouts = [...histDates, f.fecha_checkout || ''].filter(d => d);
            let prevCheckin = f.fecha_checkin || f.fecha_registro || '';

            if (allCheckouts.length > 0) {
              allCheckouts.forEach((checkout, i) => {
                periodos_list.push({
                  fecha_checkin: prevCheckin,
                  fecha_checkout: checkout,
                  pago_total: i === 0 ? (parseFloat(f.pago_total) || '') : '',
                  late_checkout: i < allCheckouts.length - 1 ? 'SI' : (f.late_checkout || 'NO'),
                  medio_pago: i === 0 ? (f.medio_pago || '') : '',
                  comprobante_pago: i === 0 ? (f.comprobante_pago || '') : '',
                  numero_comprobante: i === 0 ? (f.numero_comprobante || '') : '',
                  quien_cobro: i === 0 ? (f.quien_cobro || '') : ''
                });
                if (checkout) {
                  const d = new Date(checkout + 'T12:00:00');
                  d.setDate(d.getDate() + 1);
                  prevCheckin = d.toISOString().split('T')[0];
                }
              });
            } else {
              periodos_list.push({
                fecha_checkin: f.fecha_checkin || '',
                fecha_checkout: '',
                pago_total: parseFloat(f.pago_total) || '',
                late_checkout: f.late_checkout || 'NO',
                medio_pago: f.medio_pago || '',
                comprobante_pago: f.comprobante_pago || '',
                numero_comprobante: f.numero_comprobante || '',
                quien_cobro: f.quien_cobro || ''
              });
            }

            return {
              ...f,
              pax_list,
              periodos_list,
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
      const manana = new Date();
      manana.setDate(manana.getDate() + 1);
      const mananaStr = manana.toISOString().split('T')[0];
      
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
        periodos_list: [{
          fecha_checkin: hoyStr,
          fecha_checkout: mananaStr,
          pago_total: '',
          late_checkout: 'NO',
          medio_pago: '',
          comprobante_pago: '',
          numero_comprobante: '',
          quien_cobro: window.SERVER_DATA.operadorDefault || ''
        }],
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

    onCheckoutEnter(fila) {
      fila.modificado = true;
      fila.checkout_list.push({ fecha: '' });
    },

    async procederCheckout(fila) {
      if (!fila.stay_id) return;
      
      const result = await Swal.fire({
        title: '¿Confirmar Checkout?',
        text: `La habitación #${fila.hab || ''} pasará a estado de limpieza y se registrará la salida.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, hacer checkout',
        cancelButtonText: 'Cancelar'
      });

      if (result.isConfirmed) {
        this.loading = true;
        try {
          const resp = await fetch(`../../../api/rooming.php?action=checkout`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: fila.stay_id })
          });
          const json = await resp.json();
          if (json.ok) {
            Swal.fire({
              icon: 'success',
              title: 'Checkout realizado',
              text: json.msg || 'La habitación ha sido liberada correctamente.',
              timer: 2000,
              showConfirmButton: false
            });
            this.cargarDatos();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Atención',
              text: json.msg || 'No se pudo procesar el checkout.'
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
      }
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
        Swal.fire({ icon: 'info', title: 'Sin cambios', text: 'No hay filas modificadas con datos válidos para guardar.', timer: 2000, showConfirmButton: false });
        return;
      }

      const payload = aGuardar.map(f => {
        const p0 = f.periodos_list[0] || {};
        return {
          ...f,
          nombre_apellido: f.pax_list.map(p => p.nombre_apellido || '').join('\n'),
          documento_tipo: f.pax_list.map(p => p.documento_tipo || '').join('\n'),
          documento_num: f.pax_list.map(p => p.documento_num || '').join('\n'),
          nacionalidad: f.pax_list.map(p => p.nacionalidad || '').join('\n'),
          ciudad: f.pax_list.map(p => p.ciudad || '').join('\n'),
          fecha_checkin: p0.fecha_checkin || f.fecha_checkin || '',
          pago_total: p0.pago_total || '',
          late_checkout: f.periodos_list.some(p => p.late_checkout === 'SI') ? 'SI' : 'NO',
          medio_pago: p0.medio_pago || '',
          comprobante_pago: p0.comprobante_pago || '',
          numero_comprobante: p0.numero_comprobante || '',
          quien_cobro: p0.quien_cobro || '',
          fechas_checkout_all: f.periodos_list.map(p => p.fecha_checkout).filter(d => d).join('\n')
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

    estadoCheckout(f) {
      if (!f.stay_id || f.estado_stay === 'finalizado') return '';
      const periodos = f.periodos_list || [];
      if (!periodos.length) return '';
      const lastCheckout = periodos[periodos.length - 1].fecha_checkout;
      if (!lastCheckout) return '';
      const today = new Date().toISOString().split('T')[0];
      if (lastCheckout < today) return 'atrasado';
      if (lastCheckout === today) return 'hoy';
      return '';
    },

    agregarExtension(fila) {
      fila.modificado = true;
      const last = fila.periodos_list[fila.periodos_list.length - 1];
      // Marcar el período anterior como late_checkout = SI
      last.late_checkout = 'SI';
      // Calcular nuevo checkin = día siguiente al último checkout
      let newCheckin = '';
      if (last.fecha_checkout) {
        const d = new Date(last.fecha_checkout + 'T12:00:00');
        d.setDate(d.getDate() + 1);
        newCheckin = d.toISOString().split('T')[0];
      }
      fila.periodos_list.push({
        fecha_checkin: newCheckin,
        fecha_checkout: '',
        pago_total: '',
        late_checkout: 'NO',
        medio_pago: (fila.periodos_list[0] && fila.periodos_list[0].medio_pago) || '',
        comprobante_pago: '',
        numero_comprobante: '',
        quien_cobro: (fila.periodos_list[0] && fila.periodos_list[0].quien_cobro) || ''
      });
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
    },

    // ── REPORTE PAX ────────────────────────────────────────────
    abrirReportePax() {
      new bootstrap.Modal(document.getElementById('modalReportePaxV2')).show();
      this.reportePax.mes = this.filtro.mes;
      this.reportePax.anio = this.filtro.anio;
      this.cargarReportePax();
    },

    async cargarReportePax() {
      this.reportePax.cargando = true;
      this.reportePax.filas = [];
      try {
        const base = window.SERVER_DATA.apiEndpoint.replace('rooming_v2.php', 'rooming.php');
        const resp = await fetch(`${base}?action=reporte_pax&mes=${this.reportePax.mes}&anio=${this.reportePax.anio}`);
        const json = await resp.json();
        const rawFilas = json.data || [];
        this.reportePax.filas = rawFilas.map(f => ({ ...f, excluir: false }));

        setTimeout(() => {
          const container = document.getElementById('containerReportePaxV2');
          if (!container) return;
          let isDown = false, startX, scrollLeft;
          container.addEventListener('mousedown', (e) => { isDown = true; container.style.cursor = 'grabbing'; startX = e.pageX - container.offsetLeft; scrollLeft = container.scrollLeft; });
          container.addEventListener('mouseleave', () => { isDown = false; container.style.cursor = 'grab'; });
          container.addEventListener('mouseup', () => { isDown = false; container.style.cursor = 'grab'; });
          container.addEventListener('mousemove', (e) => { if (!isDown) return; e.preventDefault(); container.scrollLeft = scrollLeft - (e.pageX - container.offsetLeft - startX) * 2; });
        }, 300);
      } catch (e) {
        console.error('Error al cargar reporte PAX', e);
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el reporte PAX.' });
      } finally {
        this.reportePax.cargando = false;
      }
    },

    abrirConfigExportarV2() {
      bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExportConfigV2')).show();
    },

    confirmarExportacionV2() {
      const m = bootstrap.Modal.getInstance(document.getElementById('modalExportConfigV2'));
      if (m) m.hide();
      this.exportarReportePaxV2();
    },

    async exportarReportePaxV2() {
      if (!this.reportePax.filas || this.reportePax.filas.length === 0) return;

      const meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
      const nombreMes = meses[parseInt(this.reportePax.mes)] || 'Reporte';
      const titulo = `REPORTE ROOMING ${nombreMes.toUpperCase()} ${this.reportePax.anio}`;

      const workbook = new ExcelJS.Workbook();
      const ws = workbook.addWorksheet('Registro PAX');

      const colSpecs = this.selColumnas.filter(c => c.checked);
      if (colSpecs.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Atención', text: 'Debe seleccionar al menos una columna.' });
        return;
      }
      const labels = colSpecs.map(c => c.label);

      // Fila de título
      const titleRow = ws.addRow([titulo]);
      ws.mergeCells(1, 1, 1, labels.length);
      const tc = ws.getCell(1, 1);
      tc.font = { name: 'Arial', size: 16, bold: true };
      tc.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFADD8E6' } };
      tc.alignment = { horizontal: 'center', vertical: 'middle' };
      titleRow.height = 40;
      ws.addRow([]);

      // Encabezados
      const headerRow = ws.addRow(labels);
      headerRow.eachCell(cell => {
        cell.font = { bold: true };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFFFF00' } };
        cell.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
      });
      headerRow.height = 30;

      // Datos (excluir filas marcadas)
      this.reportePax.filas.filter(f => !f.excluir).forEach(f => {
        const fecha_checkout = f.fecha_checkout || (f.checkout_list && f.checkout_list.length ? f.checkout_list[f.checkout_list.length - 1].fecha : '');
        const fullData = [
          f.operador || '',
          f.fecha || f.fecha_registro || '',
          f.hab ? '#' + f.hab : (f.hab_numero ? '#' + f.hab_numero : ''),
          f.tipo_hab || f.tipo_hab_declarado || '',
          f.pax || f.pax_total || '',
          f.medio_reserva || '',
          f.hora_checkin || '',
          f.nombre_apellido || f.nombre_completo || '',
          f.documento_tipo || '',
          f.documento_num || '',
          f.nacionalidad || '',
          f.ciudad || '',
          f.fecha_checkin || f.fecha_registro || '',
          fecha_checkout,
          'S/ ' + parseFloat(f.pago_total || f.total_pago || 0).toFixed(2),
          (f.late_checkout === 'SI' || f.estado === 'late_checkout') ? 'SI' : 'NO',
          f.medio_pago || f.metodo_pago || '',
          f.comprobante_pago || f.tipo_comprobante || '',
          f.numero_comprobante || f.num_comprobante || '',
          f.quien_cobro || f.cobrador || f.operador || '',
          f.carro || '',
          f.observaciones || ''
        ];
        const filtered = this.selColumnas.reduce((acc, col, idx) => {
          if (col.checked) acc.push(fullData[idx]);
          return acc;
        }, []);
        const row = ws.addRow(filtered);
        row.eachCell(cell => {
          cell.border = { top: { style: 'thin' }, left: { style: 'thin' }, bottom: { style: 'thin' }, right: { style: 'thin' } };
          cell.alignment = { vertical: 'middle' };
        });
      });

      ws.columns.forEach((col, i) => { col.width = Math.max(labels[i] ? labels[i].length + 4 : 12, 14); });

      const buf = await workbook.xlsx.writeBuffer();
      const blob = new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `RegistroPAX_${nombreMes}_${this.reportePax.anio}.xlsx`;
      a.click();
      URL.revokeObjectURL(url);
    }
  },

  mounted() {
    this.cargarDatos().then(() => {
      // QUICK CHECK-IN desde Pax Frecuentes → pre-llena una nueva fila
      const quickRaw = localStorage.getItem('quick_checkin_v2_pax');
      if (quickRaw) {
        localStorage.removeItem('quick_checkin_v2_pax');
        try {
          const d = JSON.parse(quickRaw);

          // Agregar una fila nueva con los datos del cliente
          const hoyStr = new Date().toISOString().split('T')[0];
          const manana = new Date();
          manana.setDate(manana.getDate() + 1);
          const mananaStr = manana.toISOString().split('T')[0];
          const tempId = 'new_' + Date.now();

          const nuevaFila = {
            stay_id: null,
            pax_ids: '',
            temp_id: tempId,
            operador: window.SERVER_DATA.operadorDefault || '',
            fecha: hoyStr,
            hab: '',
            tipo_hab: '',
            pax: 1,
            pax_list: [{
              nombre_apellido: d.nombre || '',
              documento_tipo: d.tipo_doc || 'DNI',
              documento_num: d.dni || '',
              nacionalidad: d.nacionalidad || 'Peruana',
              ciudad: d.ciudad || ''
            }],
            medio_reserva: 'DIRECTO',
            hora_checkin: new Date().toTimeString().slice(0, 5),
            fecha_checkin: hoyStr,
            periodos_list: [{
              fecha_checkin: hoyStr,
              fecha_checkout: mananaStr,
              pago_total: '',
              late_checkout: 'NO',
              medio_pago: '',
              comprobante_pago: '',
              numero_comprobante: '',
              quien_cobro: window.SERVER_DATA.operadorDefault || ''
            }],
            carro: '',
            observaciones: '',
            modificado: true
          };

          this.filas.push(nuevaFila);

          // Scroll al fondo y mostrar confirmación
          setTimeout(() => {
            const container = document.querySelector('.mensual-grid-container');
            if (container) container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });

            Swal.fire({
              toast: true,
              position: 'top-end',
              icon: 'success',
              title: `Datos de ${d.nombre || 'huésped'} importados. Complete la habitación y guarde los cambios.`,
              showConfirmButton: false,
              timer: 5000
            });
          }, 300);

        } catch (e) {
          console.error('Error al cargar cliente frecuente:', e);
        }
      }
    });
  }
}).mount('#app-rooming-v2');
