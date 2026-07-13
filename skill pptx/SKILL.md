---
name: dor-ticket-pptx
description: "Use this skill when the user wants a Definition of Ready (DoR) review deck for a software ticket / user story / historia de usuario, delivered as .pptx. This is the fixed 7-slide format: 1) Portada (ticket id + title), 2) Historia de Usuario (Como/Quiero/Para), 3) Criterios de Aceptación, 4) Propuesta Técnica (Frontend / API / Base de Datos), 5) Evidencia Técnica (Maqueta / Diagrama / Base de Datos), 6) Riesgos y Dependencias, 7) Definition of Done (DoD). Trigger on phrases like 'DoR', 'Definition of Ready', 'listo para desarrollo', 'sustentación de ticket', 'HU-xx' / 'HP-xx' / ticket-code + 'en powerpoint/pptx/diapositivas', or when the user supplies a user story with acceptance criteria and asks for slides to present it. Do not use for a general project pitch or Business Model Canvas — those have their own layouts."
license: Proprietary. LICENSE.txt has complete terms
---

# Definition of Ready (DoR) ticket deck → PPTX

Generates a fixed 7-slide deck from a **data-only JSON file**: cover, user story, acceptance
criteria, technical proposal (3 layers), technical evidence (3 artifacts), risks/dependencies,
and Definition of Done. The slide order and structure are fixed by the DoR checklist itself —
edit the JSON, not the slide-building logic.

This skill is a specialization of the general `pptx` skill: read that skill too for pptxgenjs
gotchas, QA steps, and image-conversion commands — this file only covers what's specific to the
DoR layout.

## Workflow

1. **Gather the 7 sections from the ticket.** If the user only gives a ticket code and title,
   ask for (or pull from the tracker, if connected) the user story, acceptance criteria, and the
   rest — don't invent acceptance criteria, risk levels, or DoD items for a real ticket. For a
   demo/example deck, it's fine to draft plausible placeholder content, but say so.
2. **Write `dor_content.json`** following the schema below.
3. **Run the generator:**
   ```bash
   node scripts/generate_dor.js dor_content.json output.pptx
   ```
4. **QA** exactly as in the `pptx` skill: `markitdown output.pptx` for content, then
   `validate.py`, then convert to images and inspect every slide.

## JSON contract

```jsonc
{
  "meta": {
    "ticket_id": "HP-8",              // required — shown as a badge on the cover and as a kicker on every slide
    "titulo": "string, required",
    "proyecto": "string, optional",
    "sprint": "string, optional",
    "responsable": "string, optional",
    "equipo": ["Nombre 1", "Nombre 2"],  // optional
    "fecha": "string, optional"
  },
  "historia_usuario": {                 // required — Como / Quiero / Para
    "como": "string",
    "quiero": "string",
    "para": "string"
  },
  "criterios_aceptacion": ["string", "string", "..."],   // required, 3-8 items recommended
  "propuesta_tecnica": {                 // required — exactly these 3 keys (the 3 layers)
    "frontend":    { "titulo": "Frontend",     "items": ["string", "..."] },
    "api":         { "titulo": "API / Backend","items": ["string", "..."] },
    "base_datos":  { "titulo": "Base de Datos","items": ["string", "..."] }
  },
  "evidencia_tecnica": {                 // required — exactly these 3 keys
    "maqueta":    { "descripcion": "string, optional", "imagen": "path/to/mockup.png (optional)" },
    "diagrama":   { "descripcion": "string, optional", "imagen": "path/to/diagram.png (optional)" },
    "base_datos": { "descripcion": "string, optional", "imagen": "path/to/er-diagram.png (optional)" }
  },
  "riesgos": [                           // required, [] is valid if there truly are none
    { "texto": "string", "nivel": "alto" }   // nivel: "alto" | "medio" | "bajo"
  ],
  "dependencias": ["string", "..."],     // required, [] is valid
  "definition_of_done": ["string", "..."], // required, 4-8 items recommended
  "paleta": { "...": "optional, see below" }
}
```

Notes:
- **`propuesta_tecnica` and `evidencia_tecnica` keys are fixed** (`frontend`/`api`/`base_datos` and
  `maqueta`/`diagrama`/`base_datos`) because the 3-column layout is baked into the script. If a
  ticket genuinely has no backend change, still include the key with an empty or minimal `items`
  list rather than omitting it — omitting collapses that column and unbalances the row.
- **`evidencia_tecnica.*.imagen` is optional.** If given and the file exists, it's embedded in the
  slide; otherwise that slot renders a dashed placeholder with the section icon — useful for decks
  built before the mockup/diagram exists yet. Prefer providing real screenshots or exported
  diagrams (Figma export, dbdiagram.io export, etc.) over the placeholder when available.
- **`riesgos[].nivel`** drives both the colored dot and the "ALTO/MEDIO/BAJO" tag — use exactly
  `alto`, `medio`, or `bajo` (lowercase); anything else falls back to `medio`'s color.
- **`paleta`** overrides any of: `secondary` (dark bg), `primary` (accent), `success`, `warning`,
  `danger`, `neutral_fill`, `card_fill`, `gray_text`, `white`. Omit entirely for the default navy/blue
  theme.

## Content guidelines (fit inside the fixed boxes)

- **Criterios de Aceptación:** 3-8 short items. They split into two columns automatically; more
  than ~4 per column, or any item over ~20 words, risks overflow — check visually.
- **Propuesta Técnica / items per layer:** 3-5 short bullets per column (Frontend/API/Base de
  Datos) — the columns are fixed height.
- **Riesgos:** keep each `texto` to one line if possible (it wraps to two, but a third line will
  overflow the row); 3-5 risks reads well.
- **Definition of Done:** 4-8 short, checkable items — these should read as a checklist a reviewer
  can literally tick off, not paragraphs.

## Customizing beyond palette

The script (`scripts/generate_dor.js`) is intentionally organized one slide per block — if a
request needs something outside the JSON contract (e.g. an 8th slide, a 4th technical layer),
edit the script directly rather than overloading the JSON. Keep the same gotchas from the `pptx`
skill in mind (layout set before `addSlide()`, no `#` in hex colors passed to pptxgenjs — but
react-icons SVG rendering *does* need `#`, which `iconToBase64Png` already normalizes internally —
fresh shadow object per shape, `margin: 0` for aligned text, etc).

## Dependencies

`pptxgenjs`, `react-icons`, `react`, `react-dom`, `sharp` (npm, global). Plus everything in the
`pptx` skill for QA/conversion: `markitdown[pptx]`, LibreOffice, `pdftoppm`.
