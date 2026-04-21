<?php
$file = 'c:/xampp/htdocs/hotel/app/Views/rooming/index.js';
$content = file_get_contents($file);

$func_add = '    const toggleStayExclusion = (titular) => {
      const sid = titular.stay_id || titular.id;
      reportePax.filas.forEach(f => {
        if ((f.stay_id || f.id) === sid) {
          f.excluir = titular.excluir;
        }
      });
    };';

// Insertar antes del return
$content = str_replace('    return {', $func_add . "\n\n    return {", $content);
// Agregar al return
$content = str_replace('return {', "return {\n      toggleStayExclusion, ", $content);

file_put_contents($file, $content);
echo "toggleStayExclusion function added to index.js.\n";
