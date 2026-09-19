function iniciarSoporte() {
  $("#cerrarModal").click(function () {
    $("#modalSoporte").fadeOut(200);
  });

  // también cerrar tocando fuera del contenido
  $("#modalSoporte").click(function (e) {
    if (e.target.id === "modalSoporte") {
      $("#modalSoporte").fadeOut(200);
    }
  });
}

function abrirModalSoporte(titulo, contenidoHTML) {
  $("#tituloModalSoporte").text(titulo);
  $(".modal-body-custom").html(contenidoHTML);

  $("#modalSoporte").css("display", "flex").hide().fadeIn(200);
}

$(document).on("click", ".btn-soporte", function (e) {
  e.preventDefault();

  let inputCorreo = $("#correo_soporte")[0];

  // ⭐ VALIDA USANDO HTML5 (no abre modal)
  if (!inputCorreo.checkValidity()) {
    inputCorreo.reportValidity(); // muestra el mensaje del navegador
    return;
  }

  let correo = inputCorreo.value.trim();

  $.post("soporte/procesar_soporte.php", { correo: correo }, function (respuesta) {
    abrirModalSoporte("Correos", respuesta);
  });
});
