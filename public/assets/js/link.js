async function generarLink() {
    const dato1 = document.getElementById('dato1').value.trim();
    const dato2 = document.getElementById('dato2').value.trim();
    if (!dato1 || !dato2) {
        alert('Debe ingresar los valores de NetflixId y SecureNetflixId');
        return;
    }
    try {
        const response = await fetch('https://apitoken-eeds.onrender.com/generate', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cookie_data: 'NetflixId=' + dato1 + ';SecureNetflixId=' + dato2 })
        });
        const data = await response.json();
        if (!response.ok || data.status !== 'success' || typeof data.login_url !== 'string' || !data.login_url) {
            alert('No se pudo generar el link');
            return;
        }
        document.getElementById('resultado_link').value = data.login_url;
        document.getElementById('fecha_expira').textContent = 'Expira: ' + (data.expires_at || '');
        // Record successful generations, never cookie values or generated URLs.
        try {
            const recorded = await fetch('soporte/link.php', {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-CSRF-Token': fmCsrfToken() },
                body: new URLSearchParams({ event: 'generated' })
            });
            if (!recorded.ok) throw new Error('No se pudo registrar el uso');
        } catch (error) {
            document.getElementById('fecha_expira').append(' · Link generado; no se pudo registrar en las estadísticas.');
        }
    } catch (error) {
        alert('Error al consumir API');
    }
}
function copiarLink() {
    const link = document.getElementById('resultado_link');
    if (link.value) navigator.clipboard.writeText(link.value);
}
function limpiarLink() {
    for (const id of ['dato1','dato2','resultado_link']) document.getElementById(id).value = '';
    document.getElementById('fecha_expira').textContent = '';
    document.getElementById('dato1').focus();
}