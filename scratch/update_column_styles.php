<?php
$file = 'c:/xampp/htdocs/hotel/app/Views/rooming/index.js';
$content = file_get_contents($file);

$new_export_logic = '    const exportarReportePax = async () => {
      if (!reportePax.filas || reportePax.filas.length === 0) return;
      
      const meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
      const nombreMes = meses[reportePax.mes] || "Reporte";
      const tituloTexto = `REPORTE ROOMING ${nombreMes.toUpperCase()} ${reportePax.anio}`;
      
      const workbook = new ExcelJS.Workbook();
      const worksheet = workbook.addWorksheet("Registro PAX");

      const colsHeaders = ["OPERADOR", "FECHA REGISTRO", "HAB", "TIPO DE HAB", "PAX", "MEDIO DE RESERVA", "HORA DE CHECK IN", "NOMBRE Y APELLIDO", "TIPO DOC", "NÚMERO", "NACIONALIDAD", "CIUDAD", "ENTRADA", "SALIDA", "PAGO TOTAL", "LATE", "METODO", "COMPROBANTE", "Nº COMPROBANTE", "QUIEN COBRO", "CARRO", "OBS"];

      // 1. TÍTULO PRINCIPAL
      const titleRow = worksheet.addRow([tituloTexto]);
      worksheet.mergeCells(1, 1, 1, colsHeaders.length);
      const titleCell = worksheet.getCell(1, 1);
      titleCell.font = { name: "Arial", size: 16, bold: true };
      titleCell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFADD8E6" } }; // Celeste
      titleCell.alignment = { horizontal: "center", vertical: "middle" };
      titleRow.height = 40;

      worksheet.addRow([]); // Fila vacía de separación

      // 2. ENCABEZADOS
      const headerRow = worksheet.addRow(colsHeaders);
      headerRow.eachCell((cell) => {
        cell.font = { bold: true, color: { argb: "FF000000" } };
        cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFFFFF00" } }; // Amarillo
        cell.border = {
          top: { style: "thin" }, left: { style: "thin" }, bottom: { style: "thin" }, right: { style: "thin" }
        };
        // AJUSTAR TEXTO EN TODOS LOS ENCABEZADOS
        cell.alignment = { 
          horizontal: "center", 
          vertical: "middle",
          wrapText: true 
        };
      });
      headerRow.height = 30;

      // 3. DATOS
      const simbolo = (m) => m === "USD" ? "$" : (m === "CLP" ? "P$" : "S/");

      reportePax.filas.forEach(f => {
        const rowData = [
          f.es_titular ? (f.operador || "") : "", f.es_titular ? (f.fecha_registro || "") : "", f.es_titular ? ("#" + (f.hab_numero || "")) : "",
          f.es_titular ? (f.tipo_hab_declarado || "") : "", f.es_titular ? (f.pax_total || "") : "", f.es_titular ? (f.medio_reserva || "") : "",
          f.es_titular ? (f.hora_checkin || "") : "", f.nombre_completo || "", f.documento_tipo || "", f.documento_num || "",
          f.nacionalidad || "", f.ciudad || "", f.es_titular ? (f.fecha_registro || "") : "", f.es_titular ? (f.fecha_checkout || "") : "",
          f.es_titular ? `${simbolo(f.moneda_pago)} ${parseFloat(f.total_pago || 0).toFixed(2)}` : "", f.es_titular ? (f.estado === "late_checkout" ? "SI" : "NO") : "",
          f.es_titular ? (f.metodo_pago || "") : "", f.es_titular ? (f.tipo_comprobante || "") : "", f.es_titular ? (f.num_comprobante || "") : "",
          f.es_titular ? (f.cobrador || "") : "", f.es_titular ? (f.carro || "") : "", f.es_titular ? (f.observaciones || "") : ""
        ];
        const dataRow = worksheet.addRow(rowData);
        dataRow.eachCell((cell, colNumber) => {
          cell.border = {
             top: { style: "thin" }, left: { style: "thin" }, bottom: { style: "thin" }, right: { style: "thin" }
          };
          cell.alignment = { 
            vertical: "middle",
            wrapText: [4, 8, 22].includes(colNumber) // AJUSTAR TIPO HAB(4), NOMBRE(8) Y OBS(22)
          };
        });
      });

      // Ajustar anchos
      worksheet.columns.forEach((column, i) => {
        const colNum = i + 1;
        if (colNum === 4) column.width = 25; // Tipo de Hab
        else if (colNum === 8) column.width = 35; // Nombre
        else if (colNum === 22) column.width = 45; // Observaciones
        else if ([1,6,9,17,18,19].includes(colNum)) column.width = 20; 
        else column.width = 12;
      });

      // Exportar
      const buffer = await workbook.xlsx.writeBuffer();
      const blob = new Blob([buffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `Registro_PAX_${nombreMes}_${reportePax.anio}.xlsx`;
      a.click();
      URL.revokeObjectURL(url);
    };';

$search = '/\s+const exportarReportePax = async \(\\) => \{.*?    \};\s+\/\/ ─/s';
$content = preg_replace($search, "\n" . $new_export_logic . "\n    // ─", $content);

file_put_contents($file, $content);
echo "Wrap Text and width updated for Room Type and Observations.\n";
