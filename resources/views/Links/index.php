
<div class="contenedor">

    <div class="module-heading"><h2>Generador de Link</h2><span class="module-category">Gestión</span></div>

    <div class="link-container">

        <div class="link-form card card-body">

            <input class="form-control form-control-sm"
                type="text"
                id="dato1"
                placeholder="Ingrese NetflixId">

            <input class="form-control form-control-sm"
                type="text"
                id="dato2"
                placeholder="Ingrese SecureNetflixId">

            <div class="link-botones">

    <button
        type="button"
        id="btnGenerar"
        class="btn-action btn-new btn btn-sm btn-primary"
        onclick="generarLink();">

        Generar Link

    </button>

    <button
        type="button"
        id="btnLimpiar"
        class="btn-action btn-new btn-delete btn btn-sm btn-outline-danger"
        onclick="limpiarLink();">

        Limpiar

    </button>

</div>

            <div class="link-resultado">

                <input class="form-control form-control-sm"
                    type="text"
                    id="resultado_link"
                    placeholder="link generado"
                    readonly>



                <button
    type="button"
    id="btnCopiar"
    class="btn-action btn-edit btn-copy btn btn-sm btn-primary"
    onclick="copiarLink();">

    <i class="fa-solid fa-copy"></i>

</button>
     <div class="link-expiration"
    id="fecha_expira">
</div>

            </div>

        </div>

    </div>

</div>
