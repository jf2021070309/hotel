/**
 * Generate a "Definition of Ready" (DoR) ticket-review deck (7 slides) from a
 * data-only JSON file. See ../SKILL.md for the JSON contract.
 *
 * Slides: 1 Portada, 2 Historia de Usuario, 3 Criterios de Aceptación,
 *         4 Propuesta Técnica (3 capas), 5 Evidencia Técnica,
 *         6 Riesgos y Dependencias, 7 Definition of Done
 *
 * Usage:
 *   node generate_dor.js <input.json> [output.pptx]
 *
 * Deps (npm install -g): pptxgenjs react-icons react react-dom sharp
 */
const pptxgen = require("pptxgenjs");
const fs = require("fs");
const path = require("path");
const React = require("react");
const ReactDOMServer = require("react-dom/server");
const sharp = require("sharp");
const Fa = require("react-icons/fa");

const inputPath = process.argv[2] || "dor_content.json";
const data = JSON.parse(fs.readFileSync(inputPath, "utf8"));

const outputPath =
  process.argv[3] ||
  `DoR_${(data.meta?.ticket_id || "ticket").replace(/[^a-z0-9]+/gi, "_")}.pptx`;

// ---------- Palette (override any of these via data.paleta) ----------
const DEFAULT_PALETTE = {
  secondary: "0B2545",   // dark navy — cover + section backgrounds
  primary: "1B98E0",     // accent blue — kickers, active states
  success: "16A34A",     // criteria met / DoD
  warning: "D97706",     // medium risk
  danger: "DC2626",      // high risk
  neutral_fill: "F3F5F7",
  card_fill: "FFFFFF",
  gray_text: "44505B",
  white: "FFFFFF",
};
const P = { ...DEFAULT_PALETTE, ...(data.paleta || {}) };

function resolveIcon(name) {
  if (!name) return null;
  return Fa[name] || null;
}
function renderIconSvg(IconComponent, color, size = 256) {
  const cssColor = /^[0-9A-Fa-f]{6}$/.test(color) ? `#${color}` : color;
  return ReactDOMServer.renderToStaticMarkup(
    React.createElement(IconComponent, { color: cssColor, size: String(size) })
  );
}
async function iconToBase64Png(IconComponent, color, size = 256) {
  const svg = renderIconSvg(IconComponent, color, size);
  const pngBuffer = await sharp(Buffer.from(svg)).png().toBuffer();
  return "image/png;base64," + pngBuffer.toString("base64");
}
const iconCache = {};
async function getIcon(name, color) {
  if (!name) return null;
  const key = name + "_" + color;
  if (!(key in iconCache)) {
    const Comp = resolveIcon(name);
    iconCache[key] = Comp ? await iconToBase64Png(Comp, color, 256) : null;
  }
  return iconCache[key];
}

const shadow = () => ({ type: "outer", color: "000000", blur: 6, offset: 2, angle: 90, opacity: 0.12 });

// Required top-level sections — fail loud instead of shipping a half-empty deck
const REQUIRED = ["meta", "historia_usuario", "criterios_aceptacion", "propuesta_tecnica", "evidencia_tecnica", "riesgos", "definition_of_done"];

async function main() {
  const missing = REQUIRED.filter((k) => data[k] === undefined);
  if (missing.length) {
    throw new Error(`dor_content.json is missing top-level keys: ${missing.join(", ")}. See SKILL.md.`);
  }

  const pres = new pptxgen();
  pres.layout = "LAYOUT_WIDE"; // 13.33 x 7.5 — must be set before addSlide()
  pres.author = data.meta?.responsable || undefined;
  pres.title = `DoR ${data.meta?.ticket_id || ""} — ${data.meta?.titulo || ""}`.trim();

  const MX = 0.6; // shared left/right margin
  const CW = 13.333 - 2 * MX;

  // Consistent content-slide header: small kicker (ticket id + slide label) + big title
  function header(slide, kicker, title) {
    slide.addText(kicker, {
      x: MX, y: 0.35, w: CW, h: 0.35, fontFace: "Calibri", fontSize: 12, bold: true,
      color: P.primary, charSpacing: 2, margin: 0,
    });
    slide.addText(title, {
      x: MX, y: 0.68, w: CW, h: 0.6, fontFace: "Cambria", fontSize: 26, bold: true,
      color: P.secondary, margin: 0,
    });
  }

  function card(slide, x, y, w, h, fillColor) {
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x, y, w, h, rectRadius: 0.08, fill: { color: fillColor }, line: { type: "none" }, shadow: shadow(),
    });
  }

  // ---------- SLIDE 1: Portada ----------
  {
    const slide = pres.addSlide();
    slide.background = { color: P.secondary };
    slide.addShape(pres.shapes.OVAL, {
      x: 10.6, y: -2.4, w: 6, h: 6, fill: { color: P.primary, transparency: 82 }, line: { type: "none" },
    });
    slide.addShape(pres.shapes.OVAL, {
      x: -2.8, y: 5.0, w: 5, h: 5, fill: { color: P.primary, transparency: 88 }, line: { type: "none" },
    });

    // Ticket badge
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: MX, y: 0.7, w: 1.9, h: 0.5, rectRadius: 0.25, fill: { color: P.primary }, line: { type: "none" },
    });
    slide.addText(data.meta?.ticket_id || "TICKET", {
      x: MX, y: 0.7, w: 1.9, h: 0.5, fontFace: "Calibri", fontSize: 15, bold: true, color: P.white,
      align: "center", valign: "middle", margin: 0,
    });

    slide.addText("DEFINITION OF READY", {
      x: MX, y: 1.5, w: 11, h: 0.4, fontFace: "Calibri", fontSize: 14, bold: true, color: "9FC6E8",
      charSpacing: 3, margin: 0,
    });
    slide.addText(data.meta?.titulo || "", {
      x: MX, y: 1.95, w: 11.8, h: 1.6, fontFace: "Cambria", fontSize: 34, bold: true, color: P.white,
      margin: 0, lineSpacingMultiple: 1.08,
    });
    const subLine = [data.meta?.proyecto, data.meta?.sprint].filter(Boolean).join("  ·  ");
    if (subLine) {
      slide.addText(subLine, {
        x: MX, y: 3.55, w: 11, h: 0.45, fontFace: "Calibri", fontSize: 15, italic: true, color: "9FC6E8", margin: 0,
      });
    }
    const metaLines = [];
    if (data.meta?.responsable) metaLines.push({ text: "Responsable: ", options: { bold: true, color: "9FC6E8" } }, { text: data.meta.responsable, options: { color: P.white, breakLine: true } });
    if (data.meta?.equipo?.length) metaLines.push({ text: "Equipo: ", options: { bold: true, color: "9FC6E8" } }, { text: data.meta.equipo.join("  |  "), options: { color: P.white, breakLine: true } });
    if (metaLines.length) {
      slide.addText(metaLines, { x: MX, y: 5.2, w: 11.8, h: 1.0, fontFace: "Calibri", fontSize: 13, margin: 0, paraSpaceAfter: 6 });
    }
    if (data.meta?.fecha) {
      slide.addText(data.meta.fecha, { x: MX, y: 6.85, w: 6, h: 0.4, fontFace: "Calibri", fontSize: 11, color: "6E86A8", margin: 0 });
    }
  }

  // ---------- SLIDE 2: Historia de Usuario ----------
  {
    const slide = pres.addSlide();
    slide.background = { color: P.white };
    header(slide, `${data.meta?.ticket_id || ""} · DEFINITION OF READY`, "Historia de Usuario");

    const rows = [
      { label: "Como", value: data.historia_usuario.como, icon: "FaUserAlt", fill: P.neutral_fill },
      { label: "Quiero", value: data.historia_usuario.quiero, icon: "FaBullseye", fill: P.neutral_fill },
      { label: "Para", value: data.historia_usuario.para, icon: "FaGift", fill: P.primary, highlight: true },
    ];
    const top = 1.7, rowH = 1.55, gap = 0.22;
    for (let i = 0; i < rows.length; i++) {
      const r = rows[i];
      const y = top + i * (rowH + gap);
      const fillColor = r.highlight ? P.primary : P.neutral_fill;
      const textColor = r.highlight ? P.white : P.secondary;
      card(slide, MX, y, CW, rowH, fillColor);
      const icon = await getIcon(r.icon, r.highlight ? "FFFFFF" : P.primary);
      if (icon) slide.addImage({ data: icon, x: MX + 0.3, y: y + 0.35, w: 0.85, h: 0.85 });
      slide.addText(r.label.toUpperCase(), {
        x: MX + 1.4, y: y + 0.22, w: CW - 1.8, h: 0.35, fontFace: "Calibri", fontSize: 12, bold: true,
        color: r.highlight ? "D7E9FA" : P.primary, charSpacing: 2, margin: 0,
      });
      slide.addText(r.value || "", {
        x: MX + 1.4, y: y + 0.55, w: CW - 1.8, h: rowH - 0.7, fontFace: "Calibri", fontSize: 16,
        color: textColor, margin: 0, valign: "top", lineSpacingMultiple: 1.1,
      });
    }
  }

  // ---------- SLIDE 3: Criterios de Aceptación ----------
  {
    const slide = pres.addSlide();
    slide.background = { color: P.white };
    header(slide, `${data.meta?.ticket_id || ""} · DEFINITION OF READY`, "Criterios de Aceptación");
    const items = data.criterios_aceptacion || [];
    slide.addText(`${items.length} criterio${items.length === 1 ? "" : "s"}`, {
      x: MX, y: 1.3, w: CW, h: 0.35, fontFace: "Calibri", fontSize: 12, italic: true, color: P.gray_text, margin: 0,
    });
    const top = 1.75, colGap = 0.3, colW = (CW - colGap) / 2;
    const half = Math.ceil(items.length / 2);
    const leftItems = items.slice(0, half);
    const rightItems = items.slice(half);
    async function criterionRow(x, y, w, text) {
      const check = await getIcon("FaCheckCircle", P.success);
      if (check) slide.addImage({ data: check, x, y: y + 0.02, w: 0.28, h: 0.28 });
      slide.addText(text, {
        x: x + 0.42, y: y - 0.06, w: w - 0.42, h: 0.7, fontFace: "Calibri", fontSize: 13.5, color: P.secondary,
        margin: 0, valign: "top", lineSpacingMultiple: 1.05,
      });
    }
    let y = top;
    for (const it of leftItems) { await criterionRow(MX, y, colW, it); y += 0.85; }
    y = top;
    for (const it of rightItems) { await criterionRow(MX + colW + colGap, y, colW, it); y += 0.85; }
  }

  // ---------- SLIDE 4: Propuesta Técnica (3 capas) ----------
  {
    const slide = pres.addSlide();
    slide.background = { color: P.white };
    header(slide, `${data.meta?.ticket_id || ""} · DEFINITION OF READY`, "Propuesta Técnica");

    const layers = [
      { key: "frontend", icon: "FaDesktop" },
      { key: "api", icon: "FaServer" },
      { key: "base_datos", icon: "FaDatabase" },
    ];
    const top = 1.7, h = 4.7, gap = 0.3;
    const w = (CW - gap * 2) / 3;
    for (let i = 0; i < layers.length; i++) {
      const layer = data.propuesta_tecnica[layers[i].key];
      if (!layer) continue;
      const x = MX + i * (w + gap);
      card(slide, x, top, w, h, P.neutral_fill);
      slide.addShape(pres.shapes.RECTANGLE, {
        x, y: top, w, h: 0.55, fill: { color: P.secondary }, line: { type: "none" },
        rectRadius: 0.08,
      });
      // mask the bottom corners of the header bar so it reads as a rounded card top
      slide.addShape(pres.shapes.RECTANGLE, { x, y: top + 0.35, w, h: 0.2, fill: { color: P.secondary }, line: { type: "none" } });
      const icon = await getIcon(layers[i].icon, "FFFFFF");
      if (icon) slide.addImage({ data: icon, x: x + 0.2, y: top + 0.13, w: 0.3, h: 0.3 });
      slide.addText(layer.titulo || layers[i].key, {
        x: x + 0.6, y: top, w: w - 0.75, h: 0.55, fontFace: "Calibri", fontSize: 14, bold: true, color: P.white,
        margin: 0, valign: "middle",
      });
      const items = (layer.items || []).map((it, idx) => ({
        text: it, options: { bullet: { code: "2022", indent: 10 }, breakLine: idx < layer.items.length - 1, color: P.gray_text },
      }));
      slide.addText(items, {
        x: x + 0.28, y: top + 0.75, w: w - 0.56, h: h - 1.0, fontFace: "Calibri", fontSize: 12.5, color: P.gray_text,
        margin: 0, valign: "top", paraSpaceAfter: 6, lineSpacingMultiple: 1.08,
      });
    }
  }

  // ---------- SLIDE 5: Evidencia Técnica ----------
  {
    const slide = pres.addSlide();
    slide.background = { color: P.white };
    header(slide, `${data.meta?.ticket_id || ""} · DEFINITION OF READY`, "Evidencia Técnica");

    const sections = [
      { key: "maqueta", label: "Maqueta", icon: "FaImage" },
      { key: "diagrama", label: "Diagrama", icon: "FaProjectDiagram" },
      { key: "base_datos", label: "Base de Datos", icon: "FaDatabase" },
    ];
    const top = 1.7, h = 4.7, gap = 0.3;
    const w = (CW - gap * 2) / 3;
    for (let i = 0; i < sections.length; i++) {
      const sec = data.evidencia_tecnica[sections[i].key];
      if (!sec) continue;
      const x = MX + i * (w + gap);
      card(slide, x, top, w, h, P.neutral_fill);
      const labelIcon = await getIcon(sections[i].icon, P.primary);
      if (labelIcon) slide.addImage({ data: labelIcon, x: x + 0.22, y: top + 0.2, w: 0.3, h: 0.3 });
      slide.addText(sections[i].label, {
        x: x + 0.6, y: top + 0.18, w: w - 0.8, h: 0.35, fontFace: "Calibri", fontSize: 13, bold: true, color: P.secondary, margin: 0,
      });
      const imgTop = top + 0.65;
      const imgH = h - 1.5;
      if (sec.imagen && fs.existsSync(sec.imagen)) {
        slide.addImage({ data: sec.imagen, x: x + 0.22, y: imgTop, w: w - 0.44, h: imgH, sizing: { type: "contain", w: w - 0.44, h: imgH } });
      } else {
        slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
          x: x + 0.22, y: imgTop, w: w - 0.44, h: imgH, rectRadius: 0.06,
          fill: { color: P.card_fill }, line: { color: "D8DEE6", width: 1, dashType: "dash" },
        });
        const bigIcon = await getIcon(sections[i].icon, "C7CFD8");
        if (bigIcon) slide.addImage({ data: bigIcon, x: x + w / 2 - 0.5, y: imgTop + imgH / 2 - 0.85, w: 1, h: 1 });
      }
      if (sec.descripcion) {
        slide.addText(sec.descripcion, {
          x: x + 0.22, y: imgTop + imgH + 0.1, w: w - 0.44, h: h - (imgTop + imgH - top) - 0.15,
          fontFace: "Calibri", fontSize: 10.5, color: P.gray_text, margin: 0, valign: "top", lineSpacingMultiple: 1.05,
        });
      }
    }
  }

  // ---------- SLIDE 6: Riesgos y Dependencias ----------
  {
    const slide = pres.addSlide();
    slide.background = { color: P.white };
    header(slide, `${data.meta?.ticket_id || ""} · DEFINITION OF READY`, "Riesgos y Dependencias");

    const colGap = 0.3, colW = (CW - colGap) / 2, top = 1.7, h = 4.7;
    // Riesgos
    card(slide, MX, top, colW, h, P.neutral_fill);
    const riskIcon = await getIcon("FaExclamationTriangle", P.warning);
    if (riskIcon) slide.addImage({ data: riskIcon, x: MX + 0.25, y: top + 0.22, w: 0.32, h: 0.32 });
    slide.addText("Riesgos", { x: MX + 0.65, y: top + 0.2, w: colW - 0.9, h: 0.35, fontFace: "Calibri", fontSize: 15, bold: true, color: P.secondary, margin: 0 });
    const riesgos = data.riesgos || [];
    const levelColor = (nivel) => (nivel === "alto" ? P.danger : nivel === "medio" ? P.warning : P.success);
    let ry = top + 0.75;
    for (const r of riesgos) {
      const nivel = (r.nivel || "medio").toLowerCase();
      slide.addShape(pres.shapes.OVAL, { x: MX + 0.3, y: ry + 0.08, w: 0.12, h: 0.12, fill: { color: levelColor(nivel) }, line: { type: "none" } });
      slide.addText(r.texto || r, {
        x: MX + 0.55, y: ry, w: colW - 1.5, h: 0.5, fontFace: "Calibri", fontSize: 12, color: P.gray_text, margin: 0, valign: "top", lineSpacingMultiple: 1.05,
      });
      slide.addText(nivel.toUpperCase(), {
        x: MX + colW - 1.05, y: ry, w: 0.9, h: 0.3, fontFace: "Calibri", fontSize: 9, bold: true, color: levelColor(nivel),
        align: "right", margin: 0,
      });
      ry += 0.62;
    }
    // Dependencias
    const dx = MX + colW + colGap;
    card(slide, dx, top, colW, h, P.neutral_fill);
    const depIcon = await getIcon("FaLink", P.primary);
    if (depIcon) slide.addImage({ data: depIcon, x: dx + 0.25, y: top + 0.22, w: 0.32, h: 0.32 });
    slide.addText("Dependencias", { x: dx + 0.65, y: top + 0.2, w: colW - 0.9, h: 0.35, fontFace: "Calibri", fontSize: 15, bold: true, color: P.secondary, margin: 0 });
    const deps = (data.dependencias || []).map((it, i) => ({
      text: it, options: { bullet: { code: "2022", indent: 12 }, breakLine: i < data.dependencias.length - 1, color: P.gray_text },
    }));
    slide.addText(deps, {
      x: dx + 0.3, y: top + 0.75, w: colW - 0.6, h: h - 1.0, fontFace: "Calibri", fontSize: 12.5, color: P.gray_text,
      margin: 0, valign: "top", paraSpaceAfter: 8, lineSpacingMultiple: 1.1,
    });
  }

  // ---------- SLIDE 7: Definition of Done ----------
  {
    const slide = pres.addSlide();
    slide.background = { color: P.secondary };
    slide.addShape(pres.shapes.OVAL, { x: -2.6, y: -2.6, w: 5.5, h: 5.5, fill: { color: P.primary, transparency: 85 }, line: { type: "none" } });
    slide.addText(`${data.meta?.ticket_id || ""} · DEFINITION OF READY`, {
      x: MX, y: 0.5, w: CW, h: 0.35, fontFace: "Calibri", fontSize: 12, bold: true, color: "9FC6E8", charSpacing: 2, margin: 0,
    });
    slide.addText("Definition of Done", {
      x: MX, y: 0.85, w: CW, h: 0.65, fontFace: "Cambria", fontSize: 30, bold: true, color: P.white, margin: 0,
    });
    const items = data.definition_of_done || [];
    const top = 1.9, colGap = 0.4, colW = (CW - colGap) / 2;
    const half = Math.ceil(items.length / 2);
    async function doneRow(x, y, w, text) {
      const check = await getIcon("FaCheckCircle", "6FE39B");
      if (check) slide.addImage({ data: check, x, y: y + 0.02, w: 0.26, h: 0.26 });
      slide.addText(text, { x: x + 0.4, y: y - 0.06, w: w - 0.4, h: 0.65, fontFace: "Calibri", fontSize: 13, color: P.white, margin: 0, valign: "top", lineSpacingMultiple: 1.1 });
    }
    let y = top;
    for (const it of items.slice(0, half)) { await doneRow(MX, y, colW, it); y += 0.8; }
    y = top;
    for (const it of items.slice(half)) { await doneRow(MX + colW + colGap, y, colW, it); y += 0.8; }
  }

  await pres.writeFile({ fileName: outputPath });
  console.log("done:", path.resolve(outputPath));
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
