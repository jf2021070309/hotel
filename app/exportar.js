/**
 * app/exportar.js — Utilidades de exportación PDF y Excel
 * Requiere: jsPDF, jsPDF-AutoTable, SheetJS (cargados vía CDN en head.php)
 */

/**
 * Exporta datos a PDF con tabla formateada
 * @param {string} titulo     Título del documento
 * @param {string} subtitulo  Subtítulo / rango de fechas, etc.
 * @param {Array}  columnas   [{header: 'Nombre', key: 'campo', align: 'left|right|center'}]
 * @param {Array}  filas      Array de objetos con los datos
 * @param {string} archivo    Nombre del archivo sin extensión
 */
function exportarPDF(titulo, subtitulo, columnas, filas, archivo) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

    // ── Cabecera azul ──
    doc.setFillColor(37, 99, 235);
    doc.rect(0, 0, 297, 18, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(12);
    doc.text('Hotel Manager', 10, 12);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(11);
    doc.text(titulo, 297 / 2, 12, { align: 'center' });

    // ── Subtítulo y fecha ──
    doc.setTextColor(50, 50, 50);
    doc.setFontSize(8.5);
    doc.text(subtitulo, 10, 26);
    doc.text('Generado: ' + new Date().toLocaleString('es-PE'), 287, 26, { align: 'right' });

    // ── Construir head (con alineación explícita por celda) y body como ARRAYS ──
    const head = [columnas.map(c => ({
        content: c.header,
        styles: { halign: c.align || 'left' }
    }))];
    const body = filas.map(fila => columnas.map(c => fila[c.key] !== undefined ? fila[c.key] : ''));

    // ── Estilos de columna por ÍNDICE NUMÉRICO, escalados al ancho útil de la página ──
    const PAGE_W = 277; // A4 landscape 297mm − 10mm margen izq − 10mm margen der
    const totalW = columnas.reduce(function (s, c) { return s + (c.width || 0); }, 0);
    const scale = totalW > 0 ? PAGE_W / totalW : 1;
    const columnStyles = {};
    columnas.forEach(function (c, i) {
        columnStyles[i] = { halign: c.align || 'left' };
        if (c.width) columnStyles[i].cellWidth = parseFloat((c.width * scale).toFixed(2));
    });

    doc.autoTable({
        startY: 30,
        head: head,
        body: body,
        columnStyles: columnStyles,
        headStyles: {
            fillColor: [37, 99, 235],
            textColor: 255,
            fontStyle: 'bold',
            fontSize: 8.5
        },
        bodyStyles: {
            fontSize: 8,
            textColor: 40
        },
        alternateRowStyles: {
            fillColor: [248, 250, 252]
        },
        margin: { left: 10, right: 10 },
        tableWidth: 'auto'
    });

    // ── Pie de página ──
    const total = doc.internal.getNumberOfPages();
    for (let i = 1; i <= total; i++) {
        doc.setPage(i);
        doc.setFontSize(7.5);
        doc.setTextColor(160);
        doc.text('Página ' + i + ' de ' + total, 297 / 2, 205, { align: 'center' });
    }

    doc.save(archivo + '.pdf');
}

/**
 * Exporta datos a Excel (.xlsx)
 * @param {string} titulo    Nombre de la hoja
 * @param {Array}  columnas  [{header: 'Nombre', key: 'campo'}]
 * @param {Array}  filas     Array de objetos con los datos
 * @param {string} archivo   Nombre del archivo sin extensión
 */
function exportarExcel(titulo, columnas, filas, archivo) {
    const XLSX = window.XLSX;

    // Construir como array de arrays (AOA): cabecera + filas
    const headers = columnas.map(c => c.header);
    const rows = filas.map(fila => columnas.map(c => fila[c.key] !== undefined ? fila[c.key] : ''));

    const wsData = [headers].concat(rows);
    const ws = XLSX.utils.aoa_to_sheet(wsData);

    // Ancho de columnas automático
    ws['!cols'] = columnas.map(function (c, i) {
        var maxLen = c.header.length;
        filas.forEach(function (f) {
            var val = String(f[c.key] || '');
            if (val.length > maxLen) maxLen = val.length;
        });
        return { wch: Math.min(maxLen + 3, 42) };
    });

    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, titulo.slice(0, 31));
    XLSX.writeFile(wb, archivo + '.xlsx');
}

