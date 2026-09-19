<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit();
}

$usuario = $_SESSION['usuario'];
$nombre = isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : $usuario;


require 'conexion/conexion.php';
date_default_timezone_set('America/Lima');



$sqlGrafico = "
SELECT 
  DATE(fecha) AS fecha,
  streaming,
  COUNT(*) AS total_consultas
FROM uso_servicio
WHERE streaming IN ('1','2')
GROUP BY DATE(fecha), streaming
ORDER BY fecha ASC
";
$resGrafico = $conexion->query($sqlGrafico);


$labels = [];
$valores1 = []; // streaming = 1
$valores2 = []; // streaming = 2

$fechas_temp = []; // para evitar fechas duplicadas y mantener orden

while ($row = $resGrafico->fetch_assoc()) {
  $fecha = $row['fecha'];
  $stream = $row['streaming'];
  $total = $row['total_consultas'];

  // Registrar fechas únicas
  if (!in_array($fecha, $fechas_temp)) {
    $fechas_temp[] = $fecha;
  }

  // Guardar valores por streaming
  if ($stream == 1) {
    $valores1[$fecha] = $total;
  } else if ($stream == 2) {
    $valores2[$fecha] = $total;
  }
}

// Construir arrays alineados (si falta fecha → valor 0)
foreach ($fechas_temp as $f) {
  $labels[] = $f;
  $valores1_clean[] = $valores1[$f] ?? 0;
  $valores2_clean[] = $valores2[$f] ?? 0;
}


$sqlGrafico4L = "
SELECT 
  DATE(fecha) AS fecha,
  streaming,
  count(*) AS total_consultas
FROM uso_servicio
WHERE streaming IN ('3','4','5','6')
GROUP BY DATE(fecha), streaming
ORDER BY fecha ASC
";
$resGrafico4L = $conexion->query($sqlGrafico4L);

$labels_4l = [];

$valores1_4l = []; // streaming = 3
$valores2_4l = []; // streaming = 4
$valores3_4l = []; // streaming = 5
$valores4_4l = []; // streaming = 6

$valores1_clean_4l = [];
$valores2_clean_4l = [];
$valores3_clean_4l = [];
$valores4_clean_4l = [];

$fechas_temp_4l = [];

while ($row = $resGrafico4L->fetch_assoc()) {

  $fecha  = $row['fecha'];
  $stream = $row['streaming'];
  $total  = $row['total_consultas'];

  if (!in_array($fecha, $fechas_temp_4l)) {
    $fechas_temp_4l[] = $fecha;
  }

  if ($stream == 3) {
    $valores1_4l[$fecha] = $total;
  } else if ($stream == 4) {
    $valores2_4l[$fecha] = $total;
  } else if ($stream == 5) {
    $valores3_4l[$fecha] = $total;
  } else if ($stream == 6) {
    $valores4_4l[$fecha] = $total;
  }
}

foreach ($fechas_temp_4l as $f) {
  $labels_4l[] = $f;

  $valores1_clean_4l[] = $valores1_4l[$f] ?? 0;
  $valores2_clean_4l[] = $valores2_4l[$f] ?? 0;
  $valores3_clean_4l[] = $valores3_4l[$f] ?? 0;
  $valores4_clean_4l[] = $valores4_4l[$f] ?? 0;
}

/* ==========================
   🔍 DEBUG — IMPRIMIR DATA
   ========================== 
*/
/*
echo "<pre>";
echo "LABELS:\n";
print_r($labels_4l);

echo "\nSTREAMING 3:\n";
print_r($valores1_clean_4l);

echo "\nSTREAMING 4:\n";
print_r($valores2_clean_4l);

echo "\nSTREAMING 5:\n";
print_r($valores3_clean_4l);

echo "\nSTREAMING 6:\n";
print_r($valores4_clean_4l);
echo "</pre>";
*/


?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FM GROBAL - Panel</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


</head>

<body>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <span class="full">FM GROBAL</span>
      <span class="short">FM</span>
    </div>
    <ul class="menu">

      <?php
      $rol = $_SESSION["rol_id"];

      // ========================
      //  ROL 1 → admin normal
      // ========================
      if ($rol == 1) { ?>

        <li id="inicio"><i class="fa-solid fa-house"></i><span>Inicio</span></li>

        <li id="menuUsuarios"><i class="fa-solid fa-user"></i><span>Usuarios</span></li>

        <li id="Activacion"><i class="fa-solid fa-circle-nodes"></i><span>Cod. Activación</span></li>
        
        <li id="Link"><i class="fa-solid fa-link"></i><span>Link Netflix</span></li>
        
        <li id="Autoriza"><i class="fa-solid fa-at"></i><span>Autorizar Gmail</span></li>

        <li id="asesorMenu"><i class="fa-solid fa-user-tie"></i><span>Asesor</span></li>

        <li id="soporteMenu"><i class="fa-solid fa-headset"></i><span>Soporte</span></li>

        <li id="reportesMenu"><i class="fa-solid fa-chart-pie"></i><span>Reportes</span></li>

      <?php
      }
      // ========================
      //  ROL 2 → Asesor
      // ========================
      else if ($rol == 2) { ?>

        <li id="asesorMenu"><i class="fa-solid fa-user-tie"></i><span>Asesor</span></li>

      <?php
      }
      // ========================
      //  ROL 3 → Soporte
      // ========================
      else if ($rol == 3) { ?>

        <li id="soporteMenu"><i class="fa-solid fa-headset"></i><span>Soporte</span></li>

      <?php } ?>

    </ul>


  </aside>

  <div id="overlay"></div>

  <div class="main">
    <header>
      <button class="toggle-btn" id="toggle-btn">☰</button>
      <div class="user-info">
        <span class="user-name"><?php echo htmlspecialchars($nombre); ?></span>
        <a href="logout.php"><button class="logout-btn" title="Cerrar sesión"><i class="fa-solid fa-power-off"></i></button></a>
      </div>
    </header>

    <div id="contenido">
      <div id="mantenedorUsuarios"></div>
    </div>
  </div>

  <script>
    const rol_id = <?php echo $_SESSION["rol_id"]; ?>;
  </script>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="assets/js/main.js"></script>
  <script src="assets/js/usuario.js"></script>
  <script src="assets/js/activacion.js"></script>
  <script src="assets/js/asesor.js"></script>
  <script src="assets/js/soporte.js"></script>
  <script src="assets/js/link.js"></script>
  <script>
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
          labels: <?= json_encode($labels) ?>,
          datasets: [{
              label: "Netflix",
              data: <?= json_encode($valores1_clean) ?>,
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
              data: <?= json_encode($valores2_clean) ?>,
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
          labels: <?= json_encode($labels_4l) ?>,
          datasets: [

            // 🔴 STREAMING 1
            {
              label: "Netflix (Estoy de Viaje)",
              data: <?= json_encode($valores1_clean_4l) ?>,
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
              data: <?= json_encode($valores2_clean_4l) ?>,
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
              data: <?= json_encode($valores3_clean_4l) ?>,
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
              data: <?= json_encode($valores4_clean_4l) ?>,
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
  </script>

</body>

</html>