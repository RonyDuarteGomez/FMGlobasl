// La actividad real renueva la sesión; no hay un sondeo que la mantenga abierta sola.
(() => {
  const key = document.querySelector('meta[name="session-key"]')?.content;
  if (!key) return;
  const idle = Number(document.querySelector('meta[name="session-idle"]').content) * 1000;
  const storageKey = 'fm-session-' + key;
  let deadline = Date.now() + idle, lastPing = Date.now(), pending = false, leaving = false;
  let notice;
  function hideNotice() { if (notice) notice.hidden = true; }
  function remember() {
    try { localStorage.setItem(storageKey, String(deadline)); } catch (_) {}
  }
  function expire() {
    if (leaving) return;
    leaving = true;
    window.location.replace(new URL('ingresar?expired=1', document.baseURI));
  }
  function accept(remaining) {
    if (!remaining || !Number.isFinite(Number(remaining))) return;
    deadline = Date.now() + Number(remaining) * 1000;
    hideNotice(); remember();
  }
  remember();
  window.addEventListener('storage', event => {
    if (event.key === storageKey && Number(event.newValue) > deadline) {
      deadline = Number(event.newValue); hideNotice();
    }
  });
  const originalFetch = window.fetch.bind(window);
  window.fetch = async function (input, init) {
    const response = await originalFetch(input, init);
    const url = new URL(input instanceof Request ? input.url : input, location.href);
    if (url.origin === location.origin) {
      if (response.status === 401) expire();
      else accept(response.headers.get('X-Session-Remaining'));
    }
    return response;
  };
  $.ajaxPrefilter((options, original, xhr) => {
    if (new URL(options.url, location.href).origin !== location.origin) return;
    xhr.always(() => {
      if (xhr.status === 401) expire();
      else accept(xhr.getResponseHeader('X-Session-Remaining'));
    });
  });
  async function renew(force = false) {
    if (Date.now() >= deadline) { expire(); return; }
    if (pending || (!force && Date.now() - lastPing < 60000)) return;
    pending = true; lastPing = Date.now();
    try {
      const response = await fetch('session.php', { method: 'POST', credentials: 'same-origin', headers: {'X-CSRF-Token': fmCsrfToken()} });
      if (!response.ok && response.status !== 401 && notice) notice.querySelector('[data-session-message]').textContent = 'No se pudo renovar la sesión. Inténtalo nuevamente antes de que venza.';
    } catch (_) {
      if (notice) notice.querySelector('[data-session-message]').textContent = 'No hay conexión. Intenta continuar antes de que venza la sesión.';
    } finally { pending = false; }
  }
  for (const name of ['pointerdown', 'keydown', 'input', 'scroll']) {
    document.addEventListener(name, event => {
      if (event.isTrusted && !event.target.closest?.('#sessionWarning')) renew();
    }, {capture: true, passive: true});
  }
  document.addEventListener('submit', event => {
    if (Date.now() >= deadline) { event.preventDefault(); expire(); }
  }, true);
  function check() {
    const remaining = deadline - Date.now();
    if (remaining <= 0) { expire(); return; }
    if (remaining > 120000) return;
    if (!notice) {
      notice = document.createElement('div'); notice.id = 'sessionWarning';
      notice.className = 'alert alert-warning shadow session-warning';
      notice.setAttribute('role', 'alert');
      notice.innerHTML = '<div><strong>Tu sesión está por vencer</strong><p class="mb-0" data-session-message>Continúa para conservar tu sesión y los datos que estás editando.</p><small data-session-countdown></small></div><button class="btn btn-sm btn-primary" type="button">Continuar sesión</button>';
      notice.querySelector('button').addEventListener('click', () => renew(true));
      document.body.append(notice);
    }
    notice.hidden = false;
    notice.querySelector('[data-session-countdown]').textContent = 'Tiempo restante: ' + Math.ceil(remaining / 1000) + ' segundos.';
  }
  setInterval(check, 1000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) check(); });
  window.addEventListener('pageshow', check);
})();