function iniciarSoporte() {
  const element = document.getElementById('modalSoporte');
  if (!element || element.dataset.bound) return;
  element.dataset.bound = '1';
  element.addEventListener('hide.bs.modal', event => {
    if (element.dataset.busy === '1') event.preventDefault();
  });
}

$(document).on('click', '.btn-soporte', async function (event) {
  event.preventDefault();
  const input = document.getElementById('correo_soporte');
  if (!input.reportValidity()) return;
  iniciarSoporte();
  const element = document.getElementById('modalSoporte');
  if (element.dataset.busy === '1') return;
  const modal = bootstrap.Modal.getOrCreateInstance(element, { backdrop: 'static', keyboard: false });
  const body = document.getElementById('resultadoSoporte');
  const title = document.getElementById('tituloModalSoporte');
  const controls = element.querySelectorAll('[data-bs-dismiss]');
  element.dataset.busy = '1'; body.setAttribute('aria-busy', 'true');
  controls.forEach(button => button.disabled = true);
  title.textContent = 'Buscando…';
  body.innerHTML = '<div class="text-center py-5" role="status"><div class="spinner-border text-primary mb-3" aria-hidden="true"></div><p class="mb-0">Buscando…</p></div>';
  modal.show();
  try {
      body.innerHTML = await $.ajax({ url: 'soporte/procesar_soporte.php', method: 'POST', global: false, data: { correo: input.value.trim() }, timeout: 45000 });
    title.textContent = 'Correos';
  } catch (error) {
    title.textContent = 'No se pudo completar la búsqueda';
    const alert = document.createElement('div'); alert.className = 'alert alert-danger mb-0'; alert.setAttribute('role', 'alert');
    alert.textContent = error.statusText === 'timeout' ? 'La consulta está tardando demasiado. Vuelve a intentarlo.' : 'No se pudo consultar el servicio. Vuelve a intentarlo.';
    body.replaceChildren(alert);
  } finally {
    element.dataset.busy = '0'; body.removeAttribute('aria-busy');
    controls.forEach(button => button.disabled = false);
    element.querySelector('.btn-close').focus();
  }
});