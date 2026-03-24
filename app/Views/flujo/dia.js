/**
 * app/Views/flujo/dia.js
 * Vue 3 Composition API — Consolidado de Flujo del Día
 */
const { createApp, ref, onMounted } = Vue;

createApp({
  setup() {
    const BASE = '../../../api/flujo.php?action=';

    const loading = ref(true);
    const fechaFiltro = ref(SERVER_FECHA);
    const resumen = ref(null);

    const consultar = async () => {
      if (!fechaFiltro.value) return;
      loading.value = true;
      try {
        const res = await axios.get(`${BASE}resumen_dia&fecha=${fechaFiltro.value}`);
        if (res.data.ok) {
          resumen.value = res.data.data;
        }
      } catch (e) {
        console.error("Error cargando resumen", e);
      } finally {
        loading.value = false;
      }
    };

    onMounted(() => {
      consultar();
    });

    return {
      loading, fechaFiltro, resumen,
      consultar
    };
  }
}).mount('#app-flujo-dia');
