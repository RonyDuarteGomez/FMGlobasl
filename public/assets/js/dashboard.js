let graficoReportesChart = null;


    function iniciarInicio() {
      iniciarGraficoConsultas();
      iniciarTablaHome();

    }

    function iniciarGraficoConsultas() {

      const ctx = document.getElementById('graficoConsultas').getContext('2d');

      const primaryColor = getComputedStyle(document.documentElement)
        .getPropertyValue('--color-primario')
        .trim() || 'rgba(13,110,253,1)';

      function hexToRgbA(hex, alpha) {
        if (!hex.startsWith('#')) return hex;
        let c = hex.substring(1);
        if (c.length === 3) c = c.split('').map(x => x + x).join('');
        const bigint = parseInt(c, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return `rgba(${r},${g},${b},${alpha})`;
      }

      new Chart(ctx, {
        type: 'line',

        // 👇👇 ESTA ES LA PARTE QUE FALTABA
        data: {
          labels: window.fmDashboard.labels,
          datasets: [{
              label: "Netflix",
              data: window.fmDashboard.valores1_clean,
              borderWidth: 3,
              borderColor: "rgba(243, 10, 10, 1)",
              //backgroundColor: "hsla(0, 84%, 53%, 0.15)",
              fill: false,
              tension: 0,


              // ==== PUNTOS ACTIVADOS ====
              pointRadius: 3,
              pointBackgroundColor: hexToRgbA(primaryColor, 1),
              pointBorderColor: "#fff",
              pointBorderWidth: 1.8,
              pointHoverRadius: 7,
              pointHoverBackgroundColor: "#fff",
              pointHoverBorderColor: hexToRgbA(primaryColor, 1)

            },
            {
              label: "Disney",
              data: window.fmDashboard.valores2_clean,
              borderWidth: 3,
              borderColor: "rgba(6, 17, 234, 1)",
              //backgroundColor: "hsla(236, 84%, 53%, 0.15)",
              fill: false,
              tension: 0,

              // ==== PUNTOS ACTIVADOS ====
              pointRadius: 3,
              pointBackgroundColor: hexToRgbA(primaryColor, 1),
              pointBorderColor: "#fff",
              pointBorderWidth: 1.8,
              pointHoverRadius: 7,
              pointHoverBackgroundColor: "#fff",
              pointHoverBorderColor: hexToRgbA(primaryColor, 1)
            }
          ]
        },

        options: {
          responsive: true,
          maintainAspectRatio: false,

          plugins: {
            legend: {
              display: true
            }, // ahora sí mostrar leyenda
            tooltip: {
              backgroundColor: '#000',
              padding: 10,
              titleFont: {
                size: 13
              },
              bodyFont: {
                size: 12
              }
            }
          },

          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: "rgba(0,0,0,0.10)"
              },
              ticks: {
                font: {
                  size: 12
                }
              }
            },
            x: {
              grid: {
                display: true,
                color: "rgba(0,0,0,0.08)",
                drawBorder: false
              },
              ticks: {
                font: {
                  size: 12
                }
              }
            }
          }
        }
      });

    }

    function iniciarTablaHome() {
      const tabla = document.querySelector("#tablaConsultas tbody");
      const filasOriginales = Array.from(tabla.querySelectorAll("tr"));
      let filasFiltradas = [...filasOriginales];

      let paginaActual = 1;
      let registrosPorPagina = 20;

      const buscador = document.getElementById("buscadorTabla");
      const selectorRegistros = document.getElementById("registrosPorPagina");
      const paginacionDiv = document.getElementById("paginacionTabla");

      // ================================
      // 🔍 BÚSQUEDA GLOBAL
      // ================================
      buscador.addEventListener("input", () => {
        const texto = buscador.value.toLowerCase();

        filasFiltradas = filasOriginales.filter(f =>
          f.textContent.toLowerCase().includes(texto)
        );

        paginaActual = 1;
        renderTabla();
        renderPaginacion();
      });

      // ================================
      // 📌 CAMBIO DE REGISTROS POR PÁGINA
      // ================================
      selectorRegistros.addEventListener("change", () => {
        registrosPorPagina = parseInt(selectorRegistros.value);
        paginaActual = 1;
        renderTabla();
        renderPaginacion();
      });

      // ================================
      // 🧾 RENDER TABLA CON NUMERACIÓN
      // ================================
      function renderTabla() {
        tabla.innerHTML = "";

        const inicio = (paginaActual - 1) * registrosPorPagina;
        const fin = inicio + registrosPorPagina;

        const paginaFilas = filasFiltradas.slice(inicio, fin);

        paginaFilas.forEach(filaOriginal => {
          const fila = filaOriginal.cloneNode(true);
          tabla.appendChild(fila);
        });
      }

      // ================================
      // 📍 PAGINACIÓN VISUAL
      // ================================
      /*
      function renderPaginacion() {
        paginacionDiv.innerHTML = "";
        const totalPaginas = Math.ceil(filasFiltradas.length / registrosPorPagina);

        for (let i = 1; i <= totalPaginas; i++) {
          const btn = document.createElement("button");
          btn.textContent = i;

          btn.style.margin = "3px";
          btn.style.padding = "6px 10px";
          btn.style.border = "1px solid #ccc";
          btn.style.borderRadius = "6px";
          btn.style.background = (i === paginaActual) ?
            "var(--color-primario)" :
            "white";
          btn.style.color = (i === paginaActual) ? "white" : "#333";
          btn.style.cursor = "pointer";

          btn.addEventListener("click", () => {
            paginaActual = i;
            renderTabla();
            renderPaginacion();
          });

          paginacionDiv.appendChild(btn);
        }
      }
      */
      
      function renderPaginacion() {
  paginacionDiv.innerHTML = "";

  const totalPaginas = Math.ceil(filasFiltradas.length / registrosPorPagina);
  const maxPaginasVisibles = 5;

  if (totalPaginas <= 1) return;

  let inicio = Math.max(1, paginaActual - Math.floor(maxPaginasVisibles / 2));
  let fin = inicio + maxPaginasVisibles - 1;

  if (fin > totalPaginas) {
    fin = totalPaginas;
    inicio = Math.max(1, fin - maxPaginasVisibles + 1);
  }

  // ⏮ Ir al inicio
  if (paginaActual > 1) {
    crearBoton("<<", 1);
    crearBoton("<", paginaActual - 1);
  }

  // 🔢 Páginas visibles
  for (let i = inicio; i <= fin; i++) {
    crearBoton(i, i, i === paginaActual);
  }

  // ⏭ Ir al final
  if (paginaActual < totalPaginas) {
    crearBoton(">", paginaActual + 1);
    crearBoton(">>", totalPaginas);
  }
}

    function crearBoton(texto, pagina, activo = false) {
      const btn = document.createElement("button");
      btn.textContent = texto;
    
      btn.style.padding = "6px 10px";
      btn.style.borderRadius = "6px";
      btn.style.border = "1px solid #ccc";
      btn.style.cursor = "pointer";
      btn.style.background = activo ? "var(--color-primario)" : "white";
      btn.style.color = activo ? "white" : "#333";
    
      btn.addEventListener("click", () => {
        paginaActual = pagina;
        renderTabla();
        renderPaginacion();
      });
    
      paginacionDiv.appendChild(btn);
    }


      // Inicializar
      renderTabla();
      renderPaginacion();
    }


    function iniciarReportes() {
      iniciarGraficoReportes();
      iniciarTablaReportes();

    }

    function iniciarGraficoReportes() {

      const ctx = document.getElementById('graficoReportes').getContext('2d');

      const primaryColor = getComputedStyle(document.documentElement)
        .getPropertyValue('--color-primario')
        .trim() || 'rgba(13,110,253,1)';

      function hexToRgbA(hex, alpha) {
        if (!hex.startsWith('#')) return hex;
        let c = hex.substring(1);
        if (c.length === 3) c = c.split('').map(x => x + x).join('');
        const bigint = parseInt(c, 16);
        const r = (bigint >> 16) & 255;
        const g = (bigint >> 8) & 255;
        const b = bigint & 255;
        return `rgba(${r},${g},${b},${alpha})`;
      }

      if (graficoReportesChart) {
        graficoReportesChart.destroy();
      }

      graficoReportesChart = new Chart(ctx, {

        type: 'line',

        data: {
          labels: window.fmDashboard.labels_4l,
          datasets: [

            // 🔴 STREAMING 1
            {
              label: "Netflix (Estoy de Viaje)",
              data: window.fmDashboard.valores1_clean_4l,
              borderWidth: 3,
              borderColor: "rgba(243, 10, 10, 1)",
              fill: false,
              tension: 0,

              pointRadius: 3,
              pointBackgroundColor: hexToRgbA(primaryColor, 1),
              pointBorderColor: "#fff",
              pointBorderWidth: 1.8,
              pointHoverRadius: 7,
              pointHoverBackgroundColor: "#fff",
              pointHoverBorderColor: hexToRgbA(primaryColor, 1)
            },

            // 🔵 STREAMING 2
            {
              label: "Disney (Acceso Unico)",
              data: window.fmDashboard.valores2_clean_4l,
              borderWidth: 3,
              borderColor: "rgba(6, 17, 234, 1)",
              fill: false,
              tension: 0,

              pointRadius: 3,
              pointBackgroundColor: hexToRgbA(primaryColor, 1),
              pointBorderColor: "#fff",
              pointBorderWidth: 1.8,
              pointHoverRadius: 7,
              pointHoverBackgroundColor: "#fff",
              pointHoverBorderColor: hexToRgbA(primaryColor, 1)
            },

            // 🟢 STREAMING 3
            {
              label: "Netflix (Inicio Session)",
              data: window.fmDashboard.valores3_clean_4l,
              borderWidth: 3,
              borderColor: "rgba(22, 163, 74, 1)",
              fill: false,
              tension: 0,

              pointRadius: 3,
              pointBackgroundColor: hexToRgbA(primaryColor, 1),
              pointBorderColor: "#fff",
              pointBorderWidth: 1.8,
              pointHoverRadius: 7,
              pointHoverBackgroundColor: "#fff",
              pointHoverBorderColor: hexToRgbA(primaryColor, 1)
            },

            // 🟣 STREAMING 4
            {
              label: "Soporte",
              data: window.fmDashboard.valores4_clean_4l,
              borderWidth: 3,
              borderColor: "rgba(147, 51, 234, 1)",
              fill: false,
              tension: 0,

              pointRadius: 3,
              pointBackgroundColor: hexToRgbA(primaryColor, 1),
              pointBorderColor: "#fff",
              pointBorderWidth: 1.8,
              pointHoverRadius: 7,
              pointHoverBackgroundColor: "#fff",
              pointHoverBorderColor: hexToRgbA(primaryColor, 1)
            }

          ]
        },

        options: {
          responsive: true,
          maintainAspectRatio: false,

          plugins: {
            legend: {
              display: true
            },
            tooltip: {
              backgroundColor: '#000',
              padding: 10,
              titleFont: {
                size: 13
              },
              bodyFont: {
                size: 12
              }
            }
          },

          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: "rgba(0,0,0,0.10)"
              },
              ticks: {
                font: {
                  size: 12
                }
              }
            },
            x: {
              grid: {
                display: true,
                color: "rgba(0,0,0,0.08)",
                drawBorder: false
              },
              ticks: {
                font: {
                  size: 12
                }
              }
            }
          }
        }
      });
    }

    function iniciarTablaReportes() {

      const tablaBody = document.querySelector("#tablaReportes tbody");
      if (!tablaBody) return;

      const filasOriginales = Array.from(tablaBody.querySelectorAll("tr"));
      let filasFiltradas = [...filasOriginales];

      let paginaActual = 1;
      let registrosPorPagina = 20;

      const buscador = document.getElementById("buscadorReportes");
      const selectorRegistros = document.getElementById("registrosPorPaginaReportes");
      const paginacionDiv = document.getElementById("paginacionReportes");

      // 🔍 Buscador
      buscador.addEventListener("input", () => {
        const texto = buscador.value.toLowerCase();

        filasFiltradas = filasOriginales.filter(f =>
          f.textContent.toLowerCase().includes(texto)
        );

        paginaActual = 1;
        renderTabla();
        renderPaginacion();
      });

      // 📌 Registros por página
      selectorRegistros.addEventListener("change", () => {
        registrosPorPagina = parseInt(selectorRegistros.value);
        paginaActual = 1;
        renderTabla();
        renderPaginacion();
      });

      // 🧾 Render tabla
      function renderTabla() {
        tablaBody.innerHTML = "";

        const inicio = (paginaActual - 1) * registrosPorPagina;
        const fin = inicio + registrosPorPagina;

        filasFiltradas.slice(inicio, fin).forEach(fila => {
          tablaBody.appendChild(fila.cloneNode(true));
        });
      }

      // 📍 Paginación
      /*
      function renderPaginacion() {
        paginacionDiv.innerHTML = "";
        const totalPaginas = Math.ceil(filasFiltradas.length / registrosPorPagina);

        for (let i = 1; i <= totalPaginas; i++) {
          const btn = document.createElement("button");
          btn.textContent = i;

          btn.style.padding = "6px 10px";
          btn.style.borderRadius = "6px";
          btn.style.border = "1px solid #ccc";
          btn.style.cursor = "pointer";
          btn.style.background = (i === paginaActual) ?
            "var(--color-primario)" :
            "white";
          btn.style.color = (i === paginaActual) ? "white" : "#333";

          btn.addEventListener("click", () => {
            paginaActual = i;
            renderTabla();
            renderPaginacion();
          });

          paginacionDiv.appendChild(btn);
        }
      }
      */
      
      function renderPaginacion() {
  paginacionDiv.innerHTML = "";

  const totalPaginas = Math.ceil(filasFiltradas.length / registrosPorPagina);
  const maxPaginasVisibles = 5;

  if (totalPaginas <= 1) return;

  let inicio = Math.max(1, paginaActual - Math.floor(maxPaginasVisibles / 2));
  let fin = inicio + maxPaginasVisibles - 1;

  if (fin > totalPaginas) {
    fin = totalPaginas;
    inicio = Math.max(1, fin - maxPaginasVisibles + 1);
  }

  // ⏮ Ir al inicio
  if (paginaActual > 1) {
    crearBoton("<<", 1);
    crearBoton("<", paginaActual - 1);
  }

  // 🔢 Páginas visibles
  for (let i = inicio; i <= fin; i++) {
    crearBoton(i, i, i === paginaActual);
  }

  // ⏭ Ir al final
  if (paginaActual < totalPaginas) {
    crearBoton(">", paginaActual + 1);
    crearBoton(">>", totalPaginas);
  }
}

    function crearBoton(texto, pagina, activo = false) {
      const btn = document.createElement("button");
      btn.textContent = texto;
    
      btn.style.padding = "6px 10px";
      btn.style.borderRadius = "6px";
      btn.style.border = "1px solid #ccc";
      btn.style.cursor = "pointer";
      btn.style.background = activo ? "var(--color-primario)" : "white";
      btn.style.color = activo ? "white" : "#333";
    
      btn.addEventListener("click", () => {
        paginaActual = pagina;
        renderTabla();
        renderPaginacion();
      });
    
      paginacionDiv.appendChild(btn);
    }
    


      // 🚀 Init
      renderTabla();
      renderPaginacion();
    }

    // Auto iniciar
    document.addEventListener("DOMContentLoaded", iniciarTablaReportes);
  
