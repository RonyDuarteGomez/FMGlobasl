// ===== Sidebar =====
const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggle-btn");
const overlay = document.getElementById("overlay");

toggleBtn.addEventListener("click", () => {
  if (window.innerWidth > 768) {
    sidebar.classList.toggle("collapsed");
  } else {
    sidebar.style.transform = "translateX(250px)";
    overlay.classList.add("active");
  }
});

overlay.addEventListener("click", () => {
  sidebar.style.transform = "translateX(0)";
  overlay.classList.remove("active");
});

window.addEventListener("resize", () => {
  if (window.innerWidth > 768) {
    sidebar.style.transform = "";
    overlay.classList.remove("active");
  }
});

// ===== Cargar Usuarios en div =====
document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("overlay");
  const contenedorUsuarios = document.getElementById("mantenedorUsuarios");
  const contenidoInicio = document.getElementById("contenidoInicio");

  // Función para cerrar menú en móvil
  function cerrarMenuMovil() {
    if (window.innerWidth <= 768) {
      sidebar.style.transform = "translateX(0)";
      overlay.classList.remove("active");
    }
  }

  // ===== Opciones del menú =====
 
  const menuOpciones = [
    {
      id: "inicio",
      action: () => {
        contenedorUsuarios.innerHTML = "<p>Cargando reporte de inicio...</p>";
        fetch("inicio.php")
          .then((res) => res.text())
          .then((html) => {
            contenedorUsuarios.innerHTML = html;
            if (typeof iniciarInicio === "function") iniciarInicio();
          });
      },
    },
    {
      id: "menuUsuarios",
      action: () => {
        contenedorUsuarios.innerHTML = "<p>Cargando usuarios...</p>";
        fetch("usuario/usuarios.php")
          .then((res) => res.text())
          .then((html) => {
            contenedorUsuarios.innerHTML = html;
            if (typeof iniciarUsuarios === "function") iniciarUsuarios();
          });
      },
    },
    {
      id: "Activacion",
      action: () => {
        contenedorUsuarios.innerHTML = "<p>Cargando activación...</p>";
        fetch("activacion/activacion.php?t=" + Date.now())
          .then((res) => res.text())
          .then((html) => {
            contenedorUsuarios.innerHTML = html;
            if (typeof iniciarActivacion === "function") iniciarActivacion();
          });
      },
    },
    {
      id: "Link",
      action: () => {
        contenedorUsuarios.innerHTML =
          "<p>Cargando Generador de Link...</p>";
    
        fetch("soporte/link.php?t=" + Date.now())
          .then((res) => res.text())
          .then((html) => {
            contenedorUsuarios.innerHTML = html;
    
            if (typeof iniciarLink === "function")
              iniciarLink();
          });
      },
    },

    // ===== NUEVOS =====

    {
      id: "asesorMenu",
      action: () => {
        contenedorUsuarios.innerHTML = "<p>Cargando Asesor...</p>";
        fetch("soporte/asesor.php")
          .then((res) => res.text())
          .then((html) => {
            contenedorUsuarios.innerHTML = html;
            if (typeof iniciarAsesor === "function") iniciarAsesor();
          });
      },
    },

    {
      id: "soporteMenu",
      action: () => {
        contenedorUsuarios.innerHTML = "<p>Cargando Soporte...</p>";
        fetch("soporte/soporte.php")
          .then((res) => res.text())
          .then((html) => {
            contenedorUsuarios.innerHTML = html;
            if (typeof iniciarSoporte === "function") iniciarSoporte();
          });
      },
    },

    {
      id: "reportesMenu",
      action: () => {
        contenedorUsuarios.innerHTML = "<p>Cargando Reporte...</p>";
        fetch("soporte/reportes.php")
          .then((res) => res.text())
          .then((html) => {
            contenedorUsuarios.innerHTML = html;
            if (typeof iniciarReportes === "function") iniciarReportes();
          });
      },
    },
    
   
{
  id: "Autoriza",

  action: () => {

    contenedorUsuarios.innerHTML =
      "<p>Cargando Autoriza...</p>";

    fetch("mantenimiento_gmail.php")

      .then((res) => res.text())

      .then((html) => {

        contenedorUsuarios.innerHTML =
          html;

        if (
          typeof iniciarAutoriza === "function"
        ) {
          iniciarAutoriza();
        }

      });

  },
}

    
  ];

  // Asignar eventos de clic a cada opción
  menuOpciones.forEach((op) => {
    const elemento = document.getElementById(op.id);
    if (elemento) {
      elemento.addEventListener("click", () => {
        op.action();
        cerrarMenuMovil(); // cerrar sidebar en móvil
      });
    }
  });

  // ===== Cargar "Inicio" por defecto al abrir home.php =====
  if (typeof menuOpciones !== "undefined") {
    /*
    const opcionInicio = menuOpciones.find((op) => op.id === "inicio");
    if (opcionInicio && typeof opcionInicio.action === "function") {
      opcionInicio.action(); // ejecuta la acción de "inicio"
    }
      */

    // Página inicial según rol
    let opcionInicial;
    

    if (rol_id == 1) opcionInicial = "inicio";
    if (rol_id == 2) opcionInicial = "asesorMenu";
    if (rol_id == 3) opcionInicial = "soporteMenu";

    const menuInicial = menuOpciones.find(op => op.id === opcionInicial);
    if (menuInicial) menuInicial.action();



  }
});



