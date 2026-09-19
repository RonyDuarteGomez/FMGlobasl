
<div class="contenedor">

    <h2><b>Generador de Link</b></h2>

    <div class="link-container">

        <div class="link-form">

            <input
                type="text"
                id="dato1"
                placeholder="Ingrese NetflixId">

            <input
                type="text"
                id="dato2"
                placeholder="Ingrese SecureNetflixId">

            <div class="link-botones">

    <button
        type="button"
        id="btnGenerar"
        class="btn-action btn-new"
        onclick="generarLink();">

        Generar Link

    </button>

    <button
        type="button"
        id="btnLimpiar"
        class="btn-action btn-new btn-delete"
        onclick="limpiarLink();">

        Limpiar

    </button>

</div>

            <div class="link-resultado">

                <input
                    type="text"
                    id="resultado_link"
                    placeholder="link generado"
                    readonly>
                    
              

                <button
    type="button"
    id="btnCopiar"
    class="btn-action btn-edit btn-copy"
    onclick="copiarLink();">

    <i class="fa-solid fa-copy"></i>

</button>
     <div
    id="fecha_expira"
    style="
        margin-top:8px;
        color:#666;
        font-size:14px;
    ">
</div> 

            </div>

        </div>

    </div>

</div>

