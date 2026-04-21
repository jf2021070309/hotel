const { createApp, ref, computed, onMounted } = Vue;

createApp({
  setup() {
    const tab = ref('facturas');
    const loading = ref(false);
    
    const facturas = ref([]);
    const corporativas = ref([]);
    const recurrentes = ref([]);

    const busqueda = ref({
      facturas: '',
      corporativas: '',
      recurrentes: ''
    });

    const filtros = ref({
      facturas: {
        desde: new Date().toISOString().split('T')[0],
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
        f.nombre_completo.toLowerCase().includes(b) || 
        f.ruc_factura.toLowerCase().includes(b) ||
        (f.razon_social && f.razon_social.toLowerCase().includes(b))
      );
    });

    const corpFiltradas = computed(() => {
      const b = busqueda.value.corporativas.toLowerCase();
      return corporativas.value.filter(c => 
        c.empresa.toLowerCase().includes(b) || 
        (c.pais_origen && c.pais_origen.toLowerCase().includes(b))
      );
    });

    const recFiltrados = computed(() => {
      const b = busqueda.value.recurrentes.toLowerCase();
      return recurrentes.value.filter(r => 
        r.nombre_completo.toLowerCase().includes(b) || 
        (r.pais_origen && r.pais_origen.toLowerCase().includes(b))
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
        f.nombre_completo, f.documento_num, f.celular, f.ruc_factura, f.razon_social, f.total_pago, f.fecha_registro, f.num_comprobante, f.estado
      ]);
      exportarExcel('Reporte de Facturas Solicitadas', ['Huésped', 'Doc', 'Celular', 'RUC', 'Razón Social', 'Monto', 'Fecha', 'N° Comp.', 'Estado'], data, 'Facturas_Solicitadas');
    };

    const exportarCorporativas = () => {
      const data = corpFiltradas.value.map(c => [
        c.empresa, c.pais_origen, c.contacto_referencia, c.celular, c.email, c.total_estadias, c.primera_visita, c.ultima_visita
      ]);
      exportarExcel('Reporte Empresas Corporativas Extranjeras', ['Empresa', 'País', 'Contacto', 'Celular', 'Email', 'Visitas', 'Primera', 'Última'], data, 'Corporativas_Extranjeras');
    };

    const exportarRecurrentes = () => {
      const data = recFiltrados.value.map(r => [
        r.nombre_completo, r.pasaporte, r.pais_origen, r.nacionalidad, r.celular, r.email, r.total_visitas, r.primera_visita, r.ultima_visita
      ]);
      exportarExcel('Reporte Pasajeros Extranjeros Recurrentes', ['Nombre', 'Pasaporte', 'País', 'Nacionalidad', 'Celular', 'Email', 'Visitas', 'Primera', 'Última'], data, 'Pasajeros_Recurrentes');
    };

    onMounted(() => {
      cargarFacturas();
      cargarCorporativas();
      cargarRecurrentes();
    });

    return {
      tab, loading, busqueda, filtros,
      facturasFiltradas, corpFiltradas, recFiltrados,
      cargarFacturas, cargarCorporativas, cargarRecurrentes, cargarDatosActual,
      exportarFacturas, exportarCorporativas, exportarRecurrentes
    };
  }
}).mount('#app-comercial');
