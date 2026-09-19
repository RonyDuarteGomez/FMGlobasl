function iniciarAsesor() {
  $("#cerrarModal").click(function () {
    $("#modalAsesor").fadeOut(200);
  });

  // también cerrar tocando fuera del contenido
  $("#modalAsesor").click(function (e) {
    if (e.target.id === "modalAsesor") {
      $("#modalAsesor").fadeOut(200);
    }
  });
}

function abrirModalAsesor(titulo, contenidoHTML) {
  $("#tituloModalUsuario").text(titulo);
  $(".modal-body-custom").html(contenidoHTML);

  $("#modalAsesor").css("display", "flex").hide().fadeIn(200);
}

$(document).on("click", ".btn-netflix1", function (e) {
  e.preventDefault();

  let inputCorreo = $("#correo_netflix1")[0];

  // ⭐ VALIDA USANDO HTML5 (no abre modal)
  if (!inputCorreo.checkValidity()) {
    inputCorreo.reportValidity(); // muestra el mensaje del navegador
    return;
  }

  let correo = inputCorreo.value.trim();

  $.post("soporte/procesar_netflix1.php", { correo: correo }, function (respuesta) {
    abrirModalAsesor("Resultado Netflix", respuesta);
  });
});

$(document).on("click", ".btn-disney", function (e) {
  e.preventDefault();

  let inputCorreo = $("#correo_disney")[0];

  // ⭐ VALIDA USANDO HTML5 (no abre modal)
  if (!inputCorreo.checkValidity()) {
    inputCorreo.reportValidity(); // muestra el mensaje del navegador
    return;
  }

  let correo = inputCorreo.value.trim();

  $.post("soporte/procesar_disney.php", { correo: correo }, function (respuesta) {
    abrirModalAsesor("Resultado Disney", respuesta);
  });
});


$(document).on("click", ".btn-netflix2", function (e) {
  e.preventDefault();

  let inputCorreo = $("#correo_netflix2")[0];

  // ⭐ VALIDA USANDO HTML5 (no abre modal)
  if (!inputCorreo.checkValidity()) {
    inputCorreo.reportValidity(); // muestra el mensaje del navegador
    return;
  }

  let correo = inputCorreo.value.trim();

  $.post("soporte/procesar_netflix2.php", { correo: correo }, function (respuesta) {
    abrirModalAsesor("Resultado Netflix", respuesta);
  });
});