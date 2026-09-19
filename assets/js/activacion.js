function iniciarActivacion() {
  // Seleccionamos todos los botones de actualización por día
  const botones = document.querySelectorAll(".btn-actualizar-dia");

  botones.forEach((btn) => {
    btn.addEventListener("click", async (e) => {
      e.preventDefault();

      const id = btn.dataset.id;
      const dia = btn.dataset.dia;
      const inicio = document.getElementById(`hora_inicio_${id}`).value;
      const fin = document.getElementById(`hora_fin_${id}`).value;

      //alert(id);
      //alert(dia);
      //alert(inicio);
      //alert(fin);

      const datos = new FormData();
      datos.append("id", id);
      datos.append("dia", dia);
      datos.append("hora_inicio", inicio);
      datos.append("hora_fin", fin);

      try {
        const res = await fetch("activacion/actualizar_activacion.php", {
          method: "POST",
          body: datos,
        });

        const resultado = await res.text();

        if (resultado.trim() === "1") {
          alert(`${dia} actualizado correctamente`);

          // 🔁 Recargar solo el módulo de activación
          fetch("activacion/activacion.php?t=" + new Date().getTime())
            .then((res) => res.text())
            .then((html) => {
              const contenedorUsuarios =
                document.getElementById("mantenedorUsuarios");
              contenedorUsuarios.innerHTML = html;

              // ✅ Reasigna los eventos nuevamente
              if (typeof iniciarActivacion === "function") iniciarActivacion();
            })
            .catch((err) => {
              alert("⚠️ Error al recargar el módulo");
              console.error(err);
            });
        } else {
          alert(`⚠️ Error al actualizar ${dia}: ` + resultado);
        }
      } catch (err) {
        alert("⚠️ No se pudo conectar con el servidor");
      }
    });
  });
}
