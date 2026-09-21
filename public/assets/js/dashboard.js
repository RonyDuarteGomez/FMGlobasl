// Leer el tema al crear el grafico permite cambiar la paleta solo en CSS.
function colorTema(nombre) {
  return getComputedStyle(document.documentElement).getPropertyValue(nombre).trim();
}

let graficoReportesChart = null;


    function iniciarInicio() {
      iniciarGraficoConsultas();
      iniciarTablaHome();

    }

    function iniciarGraficoConsultas() {

      const ctx = document.getElementById('graficoConsultas').getContext('2d');

      const primaryColor = colorTema('--color-primario');

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
              borderColor: colorTema('--color-grafico-netflix'),
              fill: false,
              tension: 0,


              // ==== PUNTOS ACTIVADOS ====
              pointRadius: 3,
              pointBackgroundColor: hexToRgbA(primaryColor, 1),
              pointBorderColor: colorTema('--color-inverso'),
              pointBorderWidth: 1.8,
              pointHoverRadius: 7,
              pointHoverBackgroundColor: colorTema('--color-inverso'),
              pointHoverBorderColor: hexToRgbA(primaryColor, 1)

            },
            {
              label: "Disney",
              data: window.fmDashboard.valores2_clean,
              borderWidth: 3,
              borderColor: colorTema('--color-grafico-disney'),
              fill: false,
              tension: 0,

              // ==== PUNTOS ACTIVADOS ====
              pointRadius: 3,
              pointBackgroundColor: hexToRgbA(primaryColor, 1),
              pointBorderColor: colorTema('--color-inverso'),
              pointBorderWidth: 1.8,
              pointHoverRadius: 7,
              pointHoverBackgroundColor: colorTema('--color-inverso'),
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
              backgroundColor: colorTema('--color-grafico-tooltip'),
              padding: 10,
              titleFont: {
                size: parseFloat(colorTema('--texto-sm'))
              },
              bodyFont: {
                size: parseFloat(colorTema('--texto-xs'))
              }
            }
          },

          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: colorTema('--color-grafico-rejilla')
              },
              ticks: {
                font: {
                  size: parseFloat(colorTema('--texto-xs'))
                }
              }
            },
            x: {
              grid: {
                display: true,
                color: colorTema('--color-grafico-rejilla-suave'),
                drawBorder: false
              },
              ticks: {
                font: {
                  size: parseFloat(colorTema('--texto-xs'))
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
  const summary=document.createElement('span');summary.className='small text-body-secondary';summary.textContent=filasFiltradas.length+' registros · Página '+paginaActual+' de '+Math.max(1,totalPaginas);paginacionDiv.append(summary);

        for (let i = 1; i <= totalPaginas; i++) {
          const btn = document.createElement("button");
          btn.textContent = i;

          btn.className = "btn btn-sm btn-outline-primary pagination-button";
          btn.classList.toggle("is-active", i === paginaActual);
          if (i === paginaActual) btn.setAttribute("aria-current", "page");

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
  const summary=document.createElement('span');summary.className='small text-body-secondary';summary.textContent=filasFiltradas.length+' registros · Página '+paginaActual+' de '+Math.max(1,totalPaginas);paginacionDiv.append(summary);
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
    
      btn.className = "btn btn-sm btn-outline-primary pagination-button";
      btn.classList.toggle("is-active", activo);
      if (activo) btn.setAttribute("aria-current", "page");
    
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

      const primaryColor = colorTema('--color-primario');

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
              borderColor: colorTema('--color-grafico-netflix'),
              fill: false,
              tension: 0,

              pointRadius: 3,
              pointBackgroundColor: hexToRgbA(primaryColor, 1),
              pointBorderColor: colorTema('--color-inverso'),
              pointBorderWidth: 1.8,
              pointHoverRadius: 7,
              pointHoverBackgroundColor: colorTema('--color-inverso'),
              pointHoverBorderColor: hexToRgbA(primaryColor, 1)
            },

            // 🔵 STREAMING 2
            {
              label: "Disney (Acceso Unico)",
              data: window.fmDashboard.valores2_clean_4l,
              borderWidth: 3,
              borderColor: colorTema('--color-grafico-disney'),
              fill: false,
              tension: 0,

              pointRadius: 3,
              pointBackgroundColor: hexToRgbA(primaryColor, 1),
              pointBorderColor: colorTema('--color-inverso'),
              pointBorderWidth: 1.8,
              pointHoverRadius: 7,
              pointHoverBackgroundColor: colorTema('--color-inverso'),
              pointHoverBorderColor: hexToRgbA(primaryColor, 1)
            },

            // 🟢 STREAMING 3
            {
              label: "Netflix (Inicio Session)",
              data: window.fmDashboard.valores3_clean_4l,
              borderWidth: 3,
              borderColor: colorTema('--color-grafico-serie-3'),
              fill: false,
              tension: 0,

              pointRadius: 3,
              pointBackgroundColor: hexToRgbA(primaryColor, 1),
              pointBorderColor: colorTema('--color-inverso'),
              pointBorderWidth: 1.8,
              pointHoverRadius: 7,
              pointHoverBackgroundColor: colorTema('--color-inverso'),
              pointHoverBorderColor: hexToRgbA(primaryColor, 1)
            },

            // 🟣 STREAMING 4
            {
              label: "Soporte",
              data: window.fmDashboard.valores4_clean_4l,
              borderWidth: 3,
              borderColor: colorTema('--color-grafico-serie-4'),
              fill: false,
              tension: 0,

              pointRadius: 3,
              pointBackgroundColor: hexToRgbA(primaryColor, 1),
              pointBorderColor: colorTema('--color-inverso'),
              pointBorderWidth: 1.8,
              pointHoverRadius: 7,
              pointHoverBackgroundColor: colorTema('--color-inverso'),
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
              backgroundColor: colorTema('--color-grafico-tooltip'),
              padding: 10,
              titleFont: {
                size: parseFloat(colorTema('--texto-sm'))
              },
              bodyFont: {
                size: parseFloat(colorTema('--texto-xs'))
              }
            }
          },

          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: colorTema('--color-grafico-rejilla')
              },
              ticks: {
                font: {
                  size: parseFloat(colorTema('--texto-xs'))
                }
              }
            },
            x: {
              grid: {
                display: true,
                color: colorTema('--color-grafico-rejilla-suave'),
                drawBorder: false
              },
              ticks: {
                font: {
                  size: parseFloat(colorTema('--texto-xs'))
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
  const summary=document.createElement('span');summary.className='small text-body-secondary';summary.textContent=filasFiltradas.length+' registros · Página '+paginaActual+' de '+Math.max(1,totalPaginas);paginacionDiv.append(summary);

        for (let i = 1; i <= totalPaginas; i++) {
          const btn = document.createElement("button");
          btn.textContent = i;

          btn.className = "btn btn-sm btn-outline-primary pagination-button";
          btn.classList.toggle("is-active", i === paginaActual);
          if (i === paginaActual) btn.setAttribute("aria-current", "page");

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
  const summary=document.createElement('span');summary.className='small text-body-secondary';summary.textContent=filasFiltradas.length+' registros · Página '+paginaActual+' de '+Math.max(1,totalPaginas);paginacionDiv.append(summary);
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
    
      btn.className = "btn btn-sm btn-outline-primary pagination-button";
      btn.classList.toggle("is-active", activo);
      if (activo) btn.setAttribute("aria-current", "page");
    
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
  
