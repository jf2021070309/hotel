<?php
$file = 'c:/xampp/htdocs/hotel/app/Views/rooming/index.js';
$content = file_get_contents($file);

// 1. Agregar selColumnas al inicio de setup (o cerca de reportePax)
$state_add = '    const selColumnas = ref([
      { label: "OPERADOR", checked: true },
      { label: "FECHA REGISTRO", checked: true },
      { label: "HAB", checked: true },
      { label: "TIPO DE HAB", checked: true },
      { label: "PAX", checked: true },
      { label: "MEDIO DE RESERVA", checked: true },
      { label: "HORA DE CHECK IN", checked: true },
      { label: "NOMBRE Y APELLIDO", checked: true },
      { label: "TIPO DOC", checked: true },
      { label: "NÚMERO", checked: true },
      { label: "NACIONALIDAD", checked: true },
      { label: "CIUDAD", checked: true },
      { label: "ENTRADA", checked: true },
      { label: "SALIDA", checked: true },
      { label: "PAGO TOTAL", checked: true },
      { label: "LATE", checked: true },
      { label: "METODO", checked: true },
      { label: "COMPROBANTE", checked: true },
      { label: "Nº COMPROBANTE", checked: true },
      { label: "QUIEN COBRO", checked: true },
      { label: "CARRO", checked: true },
      { label: "OBS", checked: true },
    ]);

    const abrirConfigExportar = () => {
      new bootstrap.Modal("#modalExportConfig").show();
    };

    const confirmarExportacion = () => {
      const modalElem = document.getElementById("modalExportConfig");
      const modal = bootstrap.Modal.getInstance(modalElem);
      if (modal) modal.hide();
      exportarReportePax();
    };';

// Insertar el estado y funciones nuevas cerca de reportePax (lo buscaré por el nombre de la variable)
$content = str_replace('const reportePax = reactive({', $state_add . "\n\n    const reportePax = reactive({", $content);

// 2. Modificar exportarReportePax para filtrar
$new_export_logic = '    const exportarReportePax = async () => {
      if (!reportePax.filas || reportePax.filas.length === 0) return;
      
      const meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
      const nombreMes = meses[reportePax.mes] || "Reporte";
      const tituloTexto = `REPORTE ROOMING ${nombreMes.toUpperCase()} ${reportePax.anio}`;
      
      const workbook = new ExcelJS.Workbook();
      const worksheet = workbook.addWorksheet("Registro PAX");

      // Columnas seleccionadas
      const colSpecs = selColumnas.value.filter(c => c.checked);
      if (colSpecs.length === 0) {
        return showToast("Debe seleccionar al menos una columna", "warning");
      }
      const labels = colSpecs.map(c => c.label);

      // 1. TÍTULO PRINCIPAL
      const titleRow = worksheet.addRow([tituloTexto]);
      worksheet.mergeCells(1, 1, 1, labels.length);
      const titleCell = worksheet.getCell(1, 1);
      titleCell.font = { name: "Arial", size: 16, bold: true };
      titleCell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFADD8E6" } };
      titleCell.alignment = { horizontal: "center", vertical: "middle" };
      titleRow.height = 40;

      worksheet.addRow([]);

      // 2. ENCABEZADOS
      const headerRow = worksheet.addRow(labels);
      headerRow.eachCell((cell) => {
        cell.font = { bold: true, color: { argb: "FF000000" } };
        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFFFFF00" } };
        cell.border = { top: { style: "thin" }, left: { style: "thin" }, bottom: { style: "thin" }, right: { style: "thin" } };
        cell.alignment = { horizontal: "center", vertical: "middle", wrapText: true };
      });
      headerRow.height = 30;

      // 3. DATOS
      const simbolo = (m) => m === "USD" ? "$" : (m === "CLP" ? "P$" : "S/");

      reportePax.filas.forEach(f => {
        const fullData = [
          f.es_titular ? (f.operador || "") : "", f.es_titular ? (f.fecha_registro || "") : "", f.es_titular ? ("#" + (f.hab_numero || "")) : "",
          f.es_titular ? (f.tipo_hab_declarado || "") : "", f.es_titular ? (f.pax_total || "") : "", f.es_titular ? (f.medio_reserva || "") : "",
          f.es_titular ? (f.hora_checkin || "") : "", f.nombre_completo || "", f.documento_tipo || "", f.documento_num || "",
          f.nacionalidad || "", f.ciudad || "", f.es_titular ? (f.fecha_registro || "") : "", f.es_titular ? (f.fecha_checkout || "") : "",
          f.es_titular ? `${simbolo(f.moneda_pago)} ${parseFloat(f.total_pago || 0).toFixed(2)}` : "", f.es_titular ? (f.estado === "late_checkout" ? "SI" : "NO") : "",
          f.es_titular ? (f.metodo_pago || "") : "", f.es_titular ? (f.tipo_comprobante || "") : "", f.es_titular ? (f.num_comprobante || "") : "",
          f.es_titular ? (f.cobrador || "") : "", f.es_titular ? (f.carro || "") : "", f.es_titular ? (f.observaciones || "") : ""
        ];
        
        // Mapear solo los indices de las columnas seleccionadas
        const filteredData = selColumnas.value.reduce((acc, col, idx) => {
          if (col.checked) acc.push(fullData[idx]);
          return acc;
        }, []);

        const dataRow = worksheet.addRow(filteredData);
        dataRow.eachCell((cell, colIndex) => {
          cell.border = { top: { style: "thin" }, left: { style: "thin" }, bottom: { style: "thin" }, right: { style: "thin" } };
          
          // Hallar el label original para aplicar WrapText
          const currentLabel = labels[colIndex - 1];
          const needsWrap = ["TIPO DE HAB", "NOMBRE Y APELLIDO", "OBS"].includes(currentLabel);
          cell.alignment = { vertical: "middle", wrapText: needsWrap };
        });
      });

      // Anchos
      worksheet.columns.forEach((column, i) => {
        const label = labels[i];
        if (label === "TIPO DE HAB") column.width = 25;
        else if (label === "NOMBRE Y APELLIDO") column.width = 35;
        else if (label === "OBS") column.width = 45;
        else if (["OPERADOR", "MEDIO DE RESERVA", "TIPO DOC", "METODO", "COMPROBANTE", "Nº COMPROBANTE", "QUIEN COBRO"].includes(label)) column.width = 20;
        else column.width = 12;
      });

      const buffer = await workbook.xlsx.writeBuffer();
      const blob = new Blob([buffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `Registro_PAX_${nombreMes}_${reportePax.anio}.xlsx`;
      a.click();
      URL.revokeObjectURL(url);
    };';

$search_export = '/\s+const exportarReportePax = async \(\\) => \{.*?    \};\s+\/\/ ─/s';
$content = preg_replace($search_export, "\n" . $new_export_logic . "\n    // ─", $content);

// 3. Actualizar el return de setup
$return_add = "selColumnas, abrirConfigExportar, confirmarExportacion, ";
$content = str_replace('return {', "return {\n      " . $return_add, $content);

file_put_contents($file, $content);
echo "Column selector logic implemented successfully.\n";
