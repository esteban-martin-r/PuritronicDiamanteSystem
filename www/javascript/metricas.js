// metricas.js
// Maneja: filtros por AJAX (rango de fechas y comparación de meses),
// buscador del dropdown de comparación, y la gráfica de ventas por día (Chart.js).

(function () {
  "use strict";

  const contentEl = document.getElementById("dashboardContent");
  const overlayEl = document.getElementById("loadingOverlay");
  let weekdayChartInstance = null;

  // ── Gráfica de ventas por día de la semana (Chart.js) ──────────────────
  function initWeekdayChart() {
    const dataScript = document.getElementById("weekdayChartData");
    const canvas = document.getElementById("weekdayChart");
    if (!dataScript || !canvas) return;

    let data;
    try {
      data = JSON.parse(dataScript.textContent);
    } catch (e) {
      console.error("No se pudo leer los datos de la gráfica semanal:", e);
      return;
    }

    if (weekdayChartInstance) {
      weekdayChartInstance.destroy();
      weekdayChartInstance = null;
    }

    const colores = data.labels.map((dia) =>
      dia === data.diaMax ? "#4f46e5" : "#c7d2fe",
    );

    weekdayChartInstance = new Chart(canvas, {
      type: "bar",
      data: {
        labels: data.labels,
        datasets: [
          {
            label: "Promedio de garrafones",
            data: data.promedios,
            backgroundColor: colores,
            borderRadius: 8,
            maxBarThickness: 56,
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const idx = ctx.dataIndex;
                return [
                  `Promedio: ${data.promedios[idx]} garrafones`,
                  `Ventas registradas: ${data.ventas[idx]}`,
                ];
              },
            },
          },
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: "#eef0f3" },
          },
          x: {
            grid: { display: false },
          },
        },
      },
    });
  }

  // ── Buscador dentro del dropdown "Seleccionar para comparar" ───────────
  function initCompareSearch() {
    const searchInputs = contentEl.querySelectorAll(".compare-search");
    searchInputs.forEach((input) => {
      input.addEventListener("input", () => {
        const term = input.value.trim().toLowerCase();
        const dropdown = input.closest(".dropdown-menu-compare");
        if (!dropdown) return;

        const opciones = dropdown.querySelectorAll(".compare-option");
        const headers = dropdown.querySelectorAll(".year-group-header");

        opciones.forEach((op) => {
          const match = op.dataset.search.includes(term);
          op.style.display = match ? "" : "none";
        });

        // Oculta el encabezado del año si ninguna opción de ese año quedó visible
        headers.forEach((header) => {
          let el = header.nextElementSibling;
          let algunaVisible = false;
          while (el && !el.classList.contains("year-group-header")) {
            if (
              el.classList.contains("compare-option") &&
              el.style.display !== "none"
            ) {
              algunaVisible = true;
            }
            el = el.nextElementSibling;
          }
          header.style.display = algunaVisible ? "" : "none";
        });
      });

      // Evita que el dropdown se cierre al hacer clic/escribir en el buscador
      input.addEventListener("click", (e) => e.stopPropagation());
    });
  }

  // ── Carga por AJAX ───────────────────────────────────────────────────
  function mostrarCarga(mostrar) {
    if (mostrar) {
      contentEl.classList.add("is-loading");
      overlayEl.classList.add("active");
    } else {
      contentEl.classList.remove("is-loading");
      overlayEl.classList.remove("active");
    }
  }

  async function cargarContenido(url) {
    mostrarCarga(true);
    try {
      const urlConAjax = new URL(url, window.location.href);
      urlConAjax.searchParams.set("ajax", "1");

      const resp = await fetch(urlConAjax.toString(), {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      if (!resp.ok) throw new Error("Respuesta no válida del servidor");

      const html = await resp.text();
      contentEl.innerHTML = html;

      // Actualiza la URL visible en el navegador (sin el parámetro ajax), para que sea compartible/recargable
      const urlVisible = new URL(url, window.location.href);
      window.history.pushState({}, "", urlVisible.toString());

      // Reinicializa todo lo que depende del DOM recién insertado
      initWeekdayChart();
      initCompareSearch();
    } catch (err) {
      console.error("Error al cargar el dashboard:", err);
      contentEl.insertAdjacentHTML(
        "afterbegin",
        '<div class="alert alert-danger">No se pudo actualizar la información. Intenta de nuevo.</div>',
      );
    } finally {
      mostrarCarga(false);
    }
  }

  // Intercepta el envío de formularios de filtro (rango de fechas, comparación de meses)
  document.addEventListener("submit", (e) => {
    const form = e.target;
    if (!form.classList || !form.classList.contains("ajax-filter-form")) return;

    e.preventDefault();
    const params = new URLSearchParams(new FormData(form));
    const url = `${window.location.pathname}?${params.toString()}`;
    cargarContenido(url);
  });

  // Intercepta los links de "limpiar filtro" / "quitar filtro"
  document.addEventListener("click", (e) => {
    const link = e.target.closest(".ajax-filter-link");
    if (!link) return;

    e.preventDefault();
    cargarContenido(link.getAttribute("href"));
  });

  // Soporte para el botón "atrás/adelante" del navegador
  window.addEventListener("popstate", () => {
    cargarContenido(window.location.href);
  });

  // Inicialización en la carga normal de la página
  document.addEventListener("DOMContentLoaded", () => {
    initWeekdayChart();
    initCompareSearch();
  });
})();
