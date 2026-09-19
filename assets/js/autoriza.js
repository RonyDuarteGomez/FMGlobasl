
function iniciarAutoriza() {

  const menuAutoriza =
    document.getElementById("Autoriza");

  if (!menuAutoriza) return;

  menuAutoriza.addEventListener("click", async (e) => {

    e.preventDefault();

    try {

      const res =
        await fetch(
          "mantenimiento_gmail.php?t=" + new Date().getTime()
        );

      const html =
        await res.text();

      const contenedor =
        document.getElementById("mantenedorUsuarios");

      contenedor.innerHTML =
        html;

    } catch (err) {

      alert(
        "⚠️ Error al cargar mantenimiento Gmail"
      );

      console.error(err);
    }

  });

}
