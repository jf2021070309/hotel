<?php
$file = 'c:/xampp/htdocs/hotel/app/Views/rooming/index.js';
$content = file_get_contents($file);

$new_function = '    const exportarReportePax = () => {
      if (!reportePax.filas || reportePax.filas.length === 0) return;
      
      const meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
      const titulo = `REPORTE ROOMING ${meses[reportePax.mes].toUpperCase()} ${reportePax.anio}`;
      
      const cols = ["OPERADOR", "FECHA REGISTRO", "HAB", "TIPO DE HAB", "PAX", "MEDIO DE RESERVA", "HORA DE CHECK IN", "NOMBRE Y APELLIDO", "TIPO DOC", "NÚMERO", "NACIONALIDAD", "CIUDAD", "ENTRADA", "SALIDA", "PAGO TOTAL", "LATE", "METODO", "COMPROBANTE", "Nº COMPROBANTE", "QUIEN COBRO", "CARRO", "OBS"];

      const simbolo = (m) => m === "USD" ? "$" : (m === "CLP" ? "P$" : "S/");

      let html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
          <meta http-equiv="content-type" content="text/plain; charset=UTF-8"/>
          <style>
            .t-main { font-weight: bold; font-size: 16pt; background-color: #ADD8E6; text-align: center; border: 1px solid #000; height: 35px; }
            .h-yell { font-weight: bold; background-color: #FFFF00; border: 1px solid #000; text-align: center; }
            .c-cell { border: 1px solid #000; }
            td { mso-number-format:"\\\\@"; }
          </style>
        </head>
        <body>
          <table border="1">
            <tr><th colspan="${cols.length}" class="t-main">${titulo}</th></tr>
            <tr>${cols.map(c => `<th class="h-yell">${c}</th>`).join(\'\')}</tr>
      `;

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
        html += `<tr>${rowData.map(d => `<td class="c-cell">${d}</td>`).join(\'\')}</tr>`;
      });

      html += "</table></body></html>";

      const blob = new Blob([html], { type: "application/vnd.ms-excel" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `Registro_PAX_${meses[reportePax.mes]}_${reportePax.anio}.xls`;
      a.click();
      URL.revokeObjectURL(url);
    };';

$search = '/\s+const exportarReportePax = \(\\) => \{.*?    \};\s+\/\/ ─/s';
$content = preg_replace($search, "\n" . $new_function . "\n    // ─", $content);

file_put_contents($file, $content);
echo "Function updated successfully.\n";
