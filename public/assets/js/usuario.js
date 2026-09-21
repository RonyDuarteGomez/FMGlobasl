// ===== Funciones de Mantenimiento de Usuarios =====
function iniciarUsuarios() {
  const userModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUsuario'));
  $('#btnNuevo').on('click', function () {
    $('#usuarioForm')[0].reset();
    $('#usuario').prop('disabled', false);
    $('#usuario_id, #personal_id').val('');
    $('#clave').prop('required', true).attr('type', 'password');
    $('#claveLabel').html('Contraseña <span class="text-danger" aria-hidden="true">*</span>');
    $('#claveHelp').text('Obligatoria para crear la cuenta.');
    $('#tituloModalUsuario').text('Nuevo usuario');
    $('#togglePassword').attr('aria-pressed', 'false').attr('aria-label', 'Mostrar contraseña').find('i').attr('class','fas fa-eye');
    userModal.show();
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



  // ---------- GUARDAR ----------
  $("#usuarioForm").submit(function (e) {
    e.preventDefault();

    if (!this.reportValidity()) return;
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
          userModal.hide();
          cargarTabla();
        }
      },
    });
  });

  // ---------- EDITAR ----------
  $(document).off("click", ".btn-edit").on("click", ".btn-edit", function () {
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

          $('#clave').prop('required', false).attr('type', 'password');
          $('#claveLabel').text('Contraseña');
          $('#claveHelp').text('Déjala vacía para conservar la contraseña actual.');
          $('#tituloModalUsuario').text('Editar usuario');
          $('#togglePassword').attr('aria-pressed', 'false').attr('aria-label', 'Mostrar contraseña').find('i').attr('class','fas fa-eye');
          userModal.show();
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
      ? "¿Estás seguro de inactivar al usuario " + this.dataset.nombre + "?"
      : "¿Estás seguro de activar al usuario " + this.dataset.nombre + "?";
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
      ? "¿Estás seguro de inactivar al usuario " + this.dataset.nombre + "?"
      : "¿Estás seguro de activar al usuario " + this.dataset.nombre + "?";

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
    $(this).attr('aria-pressed', String(type === 'text')).attr('aria-label', type === 'text' ? 'Ocultar contraseña' : 'Mostrar contraseña');
    $(this).find('i').attr('class', type === 'text' ? 'fas fa-eye-slash' : 'fas fa-eye');
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
  const estado = document.getElementById('estadoUsuarios');
  function filtrarUsuarios() {
    const texto = buscador.value.trim().toLowerCase();
    filasFiltradas = filasOriginales.filter(f =>
      (!estado.value || f.dataset.estado === estado.value) &&
      Array.from(f.cells).slice(0,6).some(cell => cell.textContent.toLowerCase().includes(texto))
    );
    paginaActual = 1;
    renderTabla();
    renderPaginacion();
  }
  buscador.addEventListener('input', filtrarUsuarios);
  estado.addEventListener('change', filtrarUsuarios);

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

    if (!mostrar.length) {
      const row = document.createElement('tr');
      const cell = document.createElement('td'); cell.colSpan = 8;
      cell.textContent = filasOriginales.length ? 'No hay usuarios que coincidan con los filtros.' : 'No hay usuarios registrados.';
      row.append(cell); tabla.append(row);
    }
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
      btn.className = "btn btn-sm btn-outline-primary pagination-button";
      btn.classList.toggle("is-active", i === paginaActual);
      if (i === paginaActual) btn.setAttribute("aria-current", "page");

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
  paginacion.innerHTML = "";

  const totalPaginas = Math.ceil(filasFiltradas.length / registrosPorPagina);
  const summary=document.createElement('span');summary.className='small text-body-secondary';summary.textContent=filasFiltradas.length+' registros · Página '+paginaActual+' de '+Math.max(1,totalPaginas);paginacion.append(summary);
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
    
      paginacion.appendChild(btn);
    }


  // Inicialización
  renderTabla();
  renderPaginacion();
}

