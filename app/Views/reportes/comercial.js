const { createApp, ref, computed, onMounted } = Vue;

createApp({
  setup() {
    const tab = ref('facturas');
    const loading = ref(false);
    
    const facturas = ref([]);
    const sunat = ref([]);
    const corporativas = ref([]);
    const recurrentes = ref([]);

    const busqueda = ref({
      facturas: '',
      sunat: '',
      corporativas: '',
      recurrentes: ''
    });

    const filtros = ref({
      facturas: {
        desde: new Date().toISOString().split('T')[0],
        hasta: new Date().toISOString().split('T')[0]
      },
      sunat: {
        desde: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        hasta: new Date().toISOString().split('T')[0]
      },
      recurrentes: {
        min: 2
      }
    });

    // COMPUTED FILTERS
    const facturasFiltradas = computed(() => {
      const b = busqueda.value.facturas.toLowerCase();
      return facturas.value.filter(f => 
        (f.nombre_razon_social || f.nombre_completo || '').toLowerCase().includes(b) || 
        (f.numero_documento || f.ruc_factura || '').toLowerCase().includes(b)
      );
    });

    const sunatFiltrado = computed(() => {
      const b = busqueda.value.sunat.toLowerCase();
      return sunat.value.filter(s => 
        (s.nombre_razon_social || '').toLowerCase().includes(b) || 
        (s.numero_documento || '').toLowerCase().includes(b) ||
        (s.num_comprobante || '').toLowerCase().includes(b) ||
        (s.tipo_comprobante || '').toLowerCase().includes(b)
      );
    });

    const corpFiltradas = computed(() => {
      const b = busqueda.value.corporativas.toLowerCase();
      return corporativas.value.filter(c => 
        c.empresa.toLowerCase().includes(b) || 
        (c.nacionalidad && c.nacionalidad.toLowerCase().includes(b))
      );
    });

    const recFiltrados = computed(() => {
      const b = busqueda.value.recurrentes.toLowerCase();
      return recurrentes.value.filter(r => 
        r.nombre_completo.toLowerCase().includes(b) || 
        (r.nacionalidad && r.nacionalidad.toLowerCase().includes(b))
      );
    });

    // ACTIONS
    const cargarFacturas = async () => {
      loading.value = true;
      try {
        const res = await axios.get(`../../../api/reportes.php?action=facturas&desde=${filtros.value.facturas.desde}&hasta=${filtros.value.facturas.hasta}`);
        facturas.value = res.data.data || [];
      } catch (err) {
        console.error(err);
      } finally {
        loading.value = false;
      }
    };

    const cargarSunat = async () => {
      loading.value = true;
      try {
        const res = await axios.get(`../../../api/reportes.php?action=sunat&desde=${filtros.value.sunat.desde}&hasta=${filtros.value.sunat.hasta}`);
        sunat.value = res.data.data || [];
      } catch (err) {
        console.error(err);
      } finally {
        loading.value = false;
      }
    };

    const cargarCorporativas = async () => {
      loading.value = true;
      try {
        const res = await axios.get('../../../api/reportes.php?action=corporativas');
        corporativas.value = res.data.data || [];
      } catch (err) {
        console.error(err);
      } finally {
        loading.value = false;
      }
    };

    const cargarRecurrentes = async () => {
      loading.value = true;
      try {
        const res = await axios.get(`../../../api/reportes.php?action=recurrentes&min=${filtros.value.recurrentes.min}`);
        recurrentes.value = res.data.data || [];
      } catch (err) {
        console.error(err);
      } finally {
        loading.value = false;
      }
    };

    const cargarDatosActual = () => {
      if (tab.value === 'facturas') cargarFacturas();
      if (tab.value === 'sunat') cargarSunat();
      if (tab.value === 'corporativas') cargarCorporativas();
      if (tab.value === 'recurrentes') cargarRecurrentes();
    };

    // EXPORT EXCEL
    const exportarExcel = async (titulo, headers, data, filename) => {
      const workbook = new ExcelJS.Workbook();
      const worksheet = workbook.addWorksheet('Reporte');

      // Título
      const titleRow = worksheet.addRow([titulo.toUpperCase()]);
      worksheet.mergeCells(1, 1, 1, headers.length);
      titleRow.getCell(1).font = { size: 14, bold: true };
      titleRow.getCell(1).alignment = { horizontal: 'center' };
      titleRow.getCell(1).fill = { type: 'pattern', pattern:'solid', fgColor:{argb:'FFDDEBF7'} };

      worksheet.addRow([]); // Espacio

      // Cabeceras
      const headerRow = worksheet.addRow(headers);
      headerRow.eachCell((cell) => {
        cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern:'solid', fgColor:{argb:'FF1A1A2E'} };
        cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
        cell.alignment = { horizontal: 'center' };
      });

      // Datos
      data.forEach(row => {
        const r = worksheet.addRow(row);
        r.eachCell((cell) => {
          cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
        });
      });

      // Ajustar anchos
      worksheet.columns.forEach(column => {
        column.width = 20;
      });

      const buffer = await workbook.xlsx.writeBuffer();
      const blob = new Blob([buffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `${filename}.xlsx`;
      a.click();
    };

    const exportarFacturas = () => {
      const data = facturasFiltradas.value.map(f => [
        f.nombre_razon_social || f.nombre_completo, f.numero_documento || f.documento_num, f.celular, f.ruc_factura, f.razon_social, f.total_pago, f.fecha_registro, f.num_comprobante, f.estado
      ]);
      exportarExcel('Reporte de Facturas Solicitadas', ['Huésped', 'Doc', 'Celular', 'RUC', 'Razón Social', 'Monto', 'Fecha', 'N° Comp.', 'Estado'], data, 'Facturas_Solicitadas');
    };

    const exportarSunat = () => {
      const data = sunatFiltrado.value.map(s => [
        s.fecha_registro, s.tipo_comprobante, s.num_comprobante || '-', s.nombre_razon_social, s.tipo_documento, s.numero_documento, s.total_pago, s.moneda_pago
      ]);
      exportarExcel('Reporte SUNAT (Excluye Ninguna)', ['Fecha', 'Comprobante', 'N° Comprobante', 'Cliente / Empresa', 'Tipo Doc', 'N° Doc', 'Total', 'Moneda'], data, 'Reporte_SUNAT');
    };

    const exportarCorporativas = () => {
      const data = corpFiltradas.value.map(c => [
        c.empresa, c.nacionalidad, c.contacto_referencia, c.celular, c.email, c.total_estadias, c.primera_visita, c.ultima_visita
      ]);
      exportarExcel('Reporte Empresas Corporativas Extranjeras', ['Empresa', 'Nacionalidad', 'Contacto', 'Celular', 'Email', 'Visitas', 'Primera', 'Última'], data, 'Corporativas_Extranjeras');
    };

    const exportarRecurrentes = () => {
      const data = recFiltrados.value.map(r => [
        r.nombre_completo, r.pasaporte, r.nacionalidad, r.celular, r.email, r.total_visitas, r.primera_visita, r.ultima_visita
      ]);
      exportarExcel('Reporte Pasajeros Extranjeros Recurrentes', ['Nombre', 'Pasaporte', 'Nacionalidad', 'Celular', 'Email', 'Visitas', 'Primera', 'Última'], data, 'Pasajeros_Recurrentes');
    };

    onMounted(() => {
      cargarFacturas();
      cargarSunat();
      cargarCorporativas();
      cargarRecurrentes();
    });

    return {
      tab, loading, busqueda, filtros,
      facturasFiltradas, sunatFiltrado, corpFiltradas, recFiltrados,
      cargarFacturas, cargarSunat, cargarCorporativas, cargarRecurrentes, cargarDatosActual,
      exportarFacturas, exportarSunat, exportarCorporativas, exportarRecurrentes
    };
  }
}).mount('#app-comercial');
