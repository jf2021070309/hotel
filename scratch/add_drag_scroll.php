<?php
$file = 'c:/xampp/htdocs/hotel/app/Views/rooming/index.js';
$content = file_get_contents($file);

// Add the helper function logic inside the setup or before
// I'll put it as a nested function inside cargarReportePax for simplicity or just global

$search = '/\s+reportePax\.filas = res\.data\.data \|\| \[\];/';
$replace = '        reportePax.filas = res.data.data || [];
        
        // Inicializar arrastre (Drag to Scroll)
        setTimeout(() => {
          const container = document.getElementById("containerReportePax");
          if (!container) return;
          
          let isDown = false;
          let startX;
          let scrollLeft;

          container.addEventListener("mousedown", (e) => {
            isDown = true;
            container.style.cursor = "grabbing";
            startX = e.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
          });
          
          container.addEventListener("mouseleave", () => {
            isDown = false;
            container.style.cursor = "grab";
          });
          
          container.addEventListener("mouseup", () => {
            isDown = false;
            container.style.cursor = "grab";
          });
          
          container.addEventListener("mousemove", (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const walk = (x - startX) * 2; // velocidad de scroll
            container.scrollLeft = scrollLeft - walk;
          });
        }, 300);';

$content = preg_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Drag-to-scroll logic added.\n";
