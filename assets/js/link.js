async function generarLink() {

    const dato1 =
        document
        .getElementById("dato1")
        .value
        .trim();

    const dato2 =
        document
        .getElementById("dato2")
        .value
        .trim();

    if (!dato1 || !dato2) {

        alert(
            "Debe ingresar los valores de NetflixId y SecureNetflixId"
        );

        return;
    }

    const payload = {

        cookie_data:
            "NetflixId=" +
            dato1 +
            ";SecureNetflixId=" +
            dato2

    };

    try {

        const response =
            await fetch(
                "https://apitoken-eeds.onrender.com/generate",
                {
                    method: "POST",

                    headers: {
                        "Content-Type":
                            "application/json"
                    },

                    body:
                        JSON.stringify(
                            payload
                        )
                }
            );

        const data =
            await response.json();

        if (
            data.status !== "success"
        ) {

            alert(
                "No se pudo generar el link"
            );

            return;
        }

        document.getElementById(
            "resultado_link"
        ).value =
            data.login_url;

        document.getElementById(
            "fecha_expira"
        ).innerHTML =
            "<b>Expira:</b> "
            +
            data.expires_at;

    }
    catch (error) {

        console.error(error);

        alert(
            "Error al consumir API"
        );
    }
}



function copiarLink() {

    const link =
        document.getElementById(
            "resultado_link"
        );

    if (!link.value)
        return;

    navigator.clipboard
        .writeText(
            link.value
        );

}

function limpiarLink() {

    document.getElementById(
        "dato1"
    ).value = "";

    document.getElementById(
        "dato2"
    ).value = "";

    document.getElementById(
        "resultado_link"
    ).value = "";

    const expira =
        document.getElementById(
            "fecha_expira"
        );

    if (expira) {

        expira.innerHTML = "";

    }

    document.getElementById(
        "dato1"
    ).focus();
}