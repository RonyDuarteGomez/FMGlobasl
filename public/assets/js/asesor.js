function iniciarAsesor() {
  const element = document.getElementById('modalAsesor');
  if (!element || element.dataset.bound) return;
  element.dataset.bound = '1';
  element.addEventListener('hide.bs.modal', event => {
    if (element.dataset.busy === '1') event.preventDefault();
  });
}

$(document).on('click', '.btn-netflix1, .btn-disney, .btn-netflix2', async function (event) {
  event.preventDefault();
  const options = this.matches('.btn-netflix1')
    ? ['correo_netflix1', 'netflix1', 'Netflix']
    : this.matches('.btn-disney')
      ? ['correo_disney', 'disney', 'Disney+']
      : ['correo_netflix2', 'netflix2', 'Netflix'];
  const input = document.getElementById(options[0]);
  if (!input.reportValidity()) return;
  iniciarAsesor();
  const element = document.getElementById('modalAsesor');
  if (element.dataset.busy === '1') return;
  const modal = bootstrap.Modal.getOrCreateInstance(element, { backdrop: 'static', keyboard: false });
  const body = document.getElementById('resultadoAsesor');
  const title = document.getElementById('tituloModalAsesor');
  const controls = element.querySelectorAll('[data-bs-dismiss]');
  element.dataset.busy = '1';
  body.setAttribute('aria-busy', 'true');
  controls.forEach(button => button.disabled = true);
  title.textContent = 'Buscando…';
  body.innerHTML = '<div class="text-center py-5" role="status"><div class="spinner-border text-primary mb-3" aria-hidden="true"></div><p class="mb-0">Buscando…</p></div>';
  modal.show();
  try {
      const html = await $.ajax({ url: 'soporte/procesar_' + options[1] + '.php', method: 'POST', global: false, data: { correo: input.value.trim() }, timeout: 45000 });
    body.innerHTML = html;
    title.textContent = 'Resultado ' + options[2];
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