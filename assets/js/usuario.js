// ===== Funciones de Mantenimiento de Usuarios =====
function iniciarUsuarios() {
  // ---------- MODAL ----------
  $("#btnNuevo").click(function () {
    $("#usuario").prop("disabled", false);
    $("#usuarioForm")[0].reset();
    $("#usuario_id").val("");
    $("#personal_id").val("");
    //$('#modalUsuario').fadeIn(200).css('display','flex');
    // OCULTA LOS HORARIOS
    $("#seccionHorarios").hide();

    $("#modalUsuario").css("display", "flex").hide().fadeIn(200);
  });

  $("#cerrarModal").click(function () {
    $("#modalUsuario").fadeOut(200);
  });

  /*window.addEventListener('click', (e) => {
        if(e.target == document.getElementById('modalUsuario')){
            $('#modalUsuario').fadeOut(200);
        }
    });*/
  $("#modalUsuario").on("click", function (e) {
    if ($(e.target).is("#modalUsuario")) {
      $(this).fadeOut(200);
    }
  });

  // ---------- TABLA ----------
  function cargarTabla() {
    $.ajax({
      url: "usuario/usuario_list.php",
      method: "GET",
      success: function (data) {
        $("#tablaUsuarios").html(data);
        iniciarPaginacionUsuarios();
      },
    });
  }
  cargarTabla();

  $("#btnGuardarUsuario").click(function (e) {
    e.preventDefault();
    $("#usuarioForm").trigger("submit");
  });

  // ---------- GUARDAR ----------
  $("#usuarioForm").submit(function (e) {
    e.preventDefault();

    // -------- VALIDACIONES --------
    const nombre = $("#nombre").val().trim();
    const rol = $("#rol").val().trim();
    const usuario = $("#usuario").val().trim();
    const clave = $("#clave").val().trim();
    const isNuevo = $("#usuario_id").val() === ""; // si está vacío = nuevo usuario

    if (nombre === "") {
      alert("Debe ingresar un nombre.");
      return;
    }

    if (rol === "") {
      alert("Debe seleccionar un rol.");
      return;
    }

    if (isNuevo) {
      if (usuario === "") {
        alert("Debe ingresar un nombre de usuario.");
        return;
      }

      if (clave === "") {
        alert("Debe ingresar una contraseña para un nuevo usuario.");
        return;
      }
    }

    $.ajax({
      url: "usuario/usuario_save.php",
      method: "POST",
      data: $(this).serialize(),
      success: function (resp) {
        alert(resp);
        if (resp.includes("correctamente")) {
          $("#modalUsuario").fadeOut(200);
          cargarTabla();
        }
      },
    });
  });

  // ---------- EDITAR ----------
  $(document).on("click", ".btn-edit", function () {
    $("#usuario").prop("disabled", true);

    let usuario_id = $(this).data("id");
    let personal_id = $(this).data("personal");
    $.ajax({
      url: "usuario/usuario_get.php",
      method: "GET",
      data: { usuario_id: usuario_id, personal_id: personal_id },
      dataType: "json",
      success: function (data) {
        if (data.success) {
          $("#usuarioForm")[0].reset();
          $("#usuario_id").val(data.usuario.id);
          $("#personal_id").val(data.personal.id);
          $("#nombre").val(data.personal.nombre);
          $("#apellido_paterno").val(data.personal.apellido_paterno);
          $("#apellido_materno").val(data.personal.apellido_materno);
          $("#correo").val(data.personal.correo);
          $("#telefono").val(data.personal.telefono);
          $("#celular").val(data.personal.celular);
          $("#cargo").val(data.personal.cargo);
          $("#usuario").val(data.usuario.usuario);
          $("#rol").val(data.personal.rol);
          $("#clave").val("");

          // ---------- HORARIOS ----------
          for (let dia = 1; dia <= 7; dia++) {
            let inicio = data.horarios[dia]?.inicio ?? "";
            let fin = data.horarios[dia]?.fin ?? "";

            // Recortar HH:MM:SS → HH
            if (inicio.includes(":")) inicio = inicio.split(":")[0];
            if (fin.includes(":")) fin = fin.split(":")[0];

            // Asegurar 2 dígitos
            inicio = inicio.toString().padStart(2, "0");
            fin = fin.toString().padStart(2, "0");

            $("#hora_inicio_" + dia).val(inicio);
            $("#hora_fin_" + dia).val(fin);
          }

          //$('#modalUsuario').fadeIn(200).css('display','flex');
          // MOSTRAR HORARIOS SOLO EN EDICIÓN
          if (data.personal.rol === 1) {
            $("#seccionHorarios").hide();
          } else {
            $("#seccionHorarios").show();
          }

          $("#modalUsuario").css("display", "flex").hide().fadeIn(200);
        } else {
          alert(data.message);
        }
      },
    });
  });

  // ---------- ACTIVAR / INACTIVAR ----------
  /*
  $(document).on("click", ".btn-delete, .btn-activate", function () {
    let usuario_id = $(this).data("id");
    let esActivo = $(this).hasClass("btn-delete");
    let mensaje = esActivo
      ? "¿Desea inactivar el usuario?"
      : "¿Desea activar el usuario?";
    if (confirm(mensaje)) {
      $.post(
        "usuario/usuario_toggle.php",
        { usuario_id: usuario_id },
        function (resp) {
          alert(resp);
          cargarTabla();
        }
      );
    }
  });
  */
  
  $(document)
  .off("click", ".btn-delete, .btn-activate")
  .on("click", ".btn-delete, .btn-activate", function () {

    let usuario_id = $(this).data("id");
    let esActivo = $(this).hasClass("btn-delete");

    let mensaje = esActivo
      ? "¿Desea inactivar el usuario?"
      : "¿Desea activar el usuario?";

    if (confirm(mensaje)) {
      $.post(
        "usuario/usuario_toggle.php",
        { usuario_id: usuario_id },
        function (resp) {
          alert(resp);
          cargarTabla();
        }
      );
    }
});

  
  
  

  // ---------- TOGGLE PASSWORD ----------
  $("#togglePassword").click(function () {
    let input = $("#clave");
    let type = input.attr("type") === "password" ? "text" : "password";
    input.attr("type", type);
    $(this).toggleClass("fa-eye fa-eye-slash");
  });
}


function iniciarPaginacionUsuarios() {
  const tabla = document.querySelector("#usuariosTable tbody");
  if (!tabla) return;

  const filasOriginales = Array.from(tabla.querySelectorAll("tr"));
  let filasFiltradas = [...filasOriginales];

  let paginaActual = 1;
  let registrosPorPagina = 20;

  const buscador = document.getElementById("buscadorUsuarios");
  const selector = document.getElementById("registrosUsuarios");
  const paginacion = document.getElementById("paginacionUsuarios");

  // ---------------------------
  // 🔍 BUSCAR
  // ---------------------------
  buscador.addEventListener("input", () => {
    const texto = buscador.value.toLowerCase();
    filasFiltradas = filasOriginales.filter((f) =>
      f.textContent.toLowerCase().includes(texto)
    );
    paginaActual = 1;
    renderTabla();
    renderPaginacion();
  });

  // ---------------------------
  // 📌 REGISTROS POR PÁGINA
  // ---------------------------
  selector.addEventListener("change", () => {
    registrosPorPagina = parseInt(selector.value);
    paginaActual = 1;
    renderTabla();
    renderPaginacion();
  });

  // ---------------------------
  // 🧾 RENDER TABLA SUAVE
  // ---------------------------
  function renderTabla() {
    tabla.innerHTML = "";

    const inicio = (paginaActual - 1) * registrosPorPagina;
    const fin = inicio + registrosPorPagina;

    const mostrar = filasFiltradas.slice(inicio, fin);

    mostrar.forEach((f) => {
      tabla.appendChild(f);
    });

    tabla.style.opacity = "0";
    setTimeout(() => (tabla.style.opacity = "1"), 120);
  }

  // ---------------------------
  // 📍 PAGINACIÓN
  // ---------------------------
 
  /*
  function renderPaginacion() {
    paginacion.innerHTML = "";
    const total = Math.ceil(filasFiltradas.length / registrosPorPagina);

    for (let i = 1; i <= total; i++) {
      const btn = document.createElement("button");
      btn.textContent = i;
      btn.style.padding = "6px 10px";
      btn.style.border = "1px solid #ccc";
      btn.style.borderRadius = "6px";
      btn.style.cursor = "pointer";
      btn.style.background =
        i === paginaActual ? "var(--color-primario)" : "white";
      btn.style.color = i === paginaActual ? "white" : "#333";

      btn.addEventListener("click", () => {
        paginaActual = i;
        renderTabla();
        renderPaginacion();
      });

      paginacion.appendChild(btn);
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


  // Inicialización
  renderTabla();
  renderPaginacion();
}

