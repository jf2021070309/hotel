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

    // Definición de Estilos Pro
    const styleTitle = {
        fill: { fgColor: { rgb: "ADD8E6" } },
        font: { bold: true, sz: 14, color: { rgb: "000000" } },
        alignment: { horizontal: "center", vertical: "center" },
        border: {
            top: { style: "thin" }, bottom: { style: "thin" },
            left: { style: "thin" }, right: { style: "thin" }
        }
    };

    const styleHeader = {
        fill: { fgColor: { rgb: "FFFF00" } },
        font: { bold: true, sz: 11 },
        alignment: { horizontal: "center", vertical: "center" },
        border: {
            top: { style: "thin" }, bottom: { style: "thin" },
            left: { style: "thin" }, right: { style: "thin" }
        }
    };

    const styleData = (align = "left") => ({
        font: { sz: 10 },
        alignment: { horizontal: align, vertical: "center" },
        border: {
            top: { style: "thin" }, bottom: { style: "thin" },
            left: { style: "thin" }, right: { style: "thin" }
        }
    });

    // 1. Construir el libro
    const wb = XLSX.utils.book_new();
    const ws = {};

    // 2. Insertar Fila de Título (Combinada)
    const titleCellRef = XLSX.utils.encode_cell({ r: 0, c: 0 });
    ws[titleCellRef] = { v: titulo.toUpperCase(), s: styleTitle };
    // Rellenamos el resto de las celdas de la primera fila para que el borde se vea completo antes de combinar
    for (let c = 1; c < columnas.length; c++) {
        ws[XLSX.utils.encode_cell({ r: 0, c: c })] = { v: "", s: styleTitle };
    }

    // 3. Insertar Fila de Cabeceras (Fila 3, dejando la 2 vacía)
    columnas.forEach((col, i) => {
        const cellRef = XLSX.utils.encode_cell({ r: 2, c: i });
        ws[cellRef] = { v: col.header.toUpperCase(), s: styleHeader };
    });

    // 4. Insertar Datos
    filas.forEach((fila, rIdx) => {
        columnas.forEach((col, cIdx) => {
            const cellRef = XLSX.utils.encode_cell({ r: rIdx + 3, c: cIdx });
            const val = fila[col.key] !== undefined ? fila[col.key] : '';
            const align = (col.key === 'pax' || col.key === 'habitacion' || col.key === 'hab') ? 'center' : 'left';
            ws[cellRef] = { v: val, s: styleData(align) };
        });
    });

    // 5. Configurar Metadatos de la Hoja
    const maxRow = filas.length + 3;
    ws['!ref'] = XLSX.utils.encode_range({ s: { r: 0, c: 0 }, e: { r: maxRow - 1, c: columnas.length - 1 } });
    
    // Combinaciones
    ws['!merges'] = [
        { s: { r: 0, c: 0 }, e: { r: 0, c: columnas.length - 1 } }
    ];

    // Col Widths
    ws['!cols'] = columnas.map(c => {
        let maxLen = c.header.length;
        filas.forEach(f => {
            const val = String(f[c.key] || '');
            if (val.length > maxLen) maxLen = val.length;
        });
        return { wch: Math.min(maxLen + 8, 50) };
    });

    // 6. Escribir y descargar
    XLSX.utils.book_append_sheet(wb, ws, "Reporte");
    XLSX.writeFile(wb, archivo + ".xlsx");
}

