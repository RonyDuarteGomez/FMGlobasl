// Una sola navegación para el menú, las tarjetas y los filtros de cada módulo.
(() => {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  const toggle = document.getElementById('toggle-btn');
  const content = document.getElementById('mantenedorUsuarios');
  let pending;
  const cleanRoutes = JSON.parse(document.getElementById('cleanRoutes')?.textContent || '{}');
  const baseUrl = new URL(document.baseURI);
  const permissionsKey = 'fm-permissions-' + baseUrl.pathname + (document.querySelector('meta[name="session-key"]')?.content || '');
  function rememberPermissions(params) {
    const selection = new URLSearchParams();
    for (const name of ['type', 'id']) if (params.has(name)) selection.set(name, params.get(name));
    try { sessionStorage.setItem(permissionsKey, selection.toString()); } catch (_) {}
  }
  function savedPermissions() {
    try { return sessionStorage.getItem(permissionsKey) || ''; } catch (_) { return ''; }
  }
  function updateAddress(button, replace = false) {
    if (!cleanRoutes[button.id]) return;
    const url = new URL('sistema/' + cleanRoutes[button.id], baseUrl);
    history[replace ? 'replaceState' : 'pushState']({}, '', url);
  }
  function syncMenuState() {
    toggle.setAttribute('aria-expanded', String(window.innerWidth <= 768
      ? sidebar.classList.contains('active') : !sidebar.classList.contains('collapsed')));
  }
  function closeMobile() {
    sidebar.classList.remove('active'); overlay.classList.remove('active'); syncMenuState();
  }
  toggle.addEventListener('click', () => {
    if (window.innerWidth <= 768) {
      sidebar.classList.remove('collapsed');
      sidebar.classList.toggle('active'); overlay.classList.toggle('active');
    } else {
      sidebar.classList.toggle('collapsed');
      if (sidebar.classList.contains('collapsed')) {
        sidebar.querySelectorAll('.nav-group').forEach(group => { group.open = false; });
      }
    }
    syncMenuState();
  });
  sidebar.addEventListener('click', event => {
    const summary = event.target.closest('.nav-group > summary');
    if (summary && sidebar.classList.contains('collapsed')) {
      event.preventDefault(); sidebar.classList.remove('collapsed');
      summary.parentElement.open = true; syncMenuState();
    }
  });
  overlay.addEventListener('click', closeMobile);
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && sidebar.classList.contains('active')) {
      closeMobile(); toggle.focus();
    }
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth > 768) closeMobile();
    else sidebar.classList.remove('collapsed');
    syncMenuState();
  });
  syncMenuState();
  async function loadModule(url, init = '') {
    pending?.abort(); pending = new AbortController();
    content.querySelectorAll('.fm-table-icon-wrap,[data-spotify-tip]').forEach(el=>bootstrap.Tooltip.getInstance(el)?.dispose());
    content.textContent = 'Cargando…'; closeMobile();
    try {
      const response = await fetch(url, { signal: pending.signal, credentials: 'same-origin' });
      if (response.status === 401) { window.location.href = new URL('ingresar?expired=1', baseUrl); return; }
      const html = await response.text();
      if (!response.ok) throw new Error(html || 'No se pudo cargar la pantalla.');
      content.innerHTML = html;
      const permissionsForm = content.querySelector('#permissionsForm');
      if (permissionsForm) rememberPermissions(new URLSearchParams(new FormData(permissionsForm)));
      if (init && typeof window[init] === 'function') window[init]();
      if (document.getElementById('tablaGmail')) initGmailTable();
    } catch (error) {
      if (error.name !== 'AbortError') content.textContent = error.message;
    }
  }
  function activate(button, historyMode = true) {
    if (!button) return;
    document.querySelectorAll('[data-module]').forEach(item => item.removeAttribute('aria-current'));
    button.setAttribute('aria-current', 'page');
    if (historyMode) updateAddress(button, historyMode === 'replace');
    const selection = button.id === 'menuPermisos' ? savedPermissions() : '';
    loadModule(button.dataset.module + (selection ? '?' + selection : ''), button.dataset.init);
  }
  document.addEventListener('click', event => {
    const module = event.target.closest('[data-module]');
    if (module) { event.preventDefault(); activate(module); return; }
    const shortcut = event.target.closest('[data-open-module]');
    if (shortcut) { activate(document.getElementById(shortcut.dataset.openModule)); return; }
    const page = event.target.closest('[data-load-url]');
    if (page) { event.preventDefault(); loadModule(page.dataset.loadUrl); }
  });
  let publicSearchTimer;
  document.addEventListener('input', event => {
    if (!event.target.matches('#publicReportFilters [name="q"]')) return;
    clearTimeout(publicSearchTimer);
    const field=event.target;
    publicSearchTimer=setTimeout(async () => {
      if (!field.isConnected) return;
      const cursor=field.selectionStart;
      await loadModule(field.form.dataset.moduleFilter + '?' + new URLSearchParams(new FormData(field.form)));
      const replacement=document.querySelector('#publicReportFilters [name="q"]');
      if(replacement) { replacement.focus(); replacement.setSelectionRange(cursor,cursor); }
    },400);
  });
  document.addEventListener('change', event => {
    if (event.target.matches('#publicReportFilters select')) {
      clearTimeout(publicSearchTimer);
      loadModule(event.target.form.dataset.moduleFilter + '?' + new URLSearchParams(new FormData(event.target.form)));
    }
    if (event.target.matches('[data-module-filter="permisos/index.php"] [name="type"]')) {
      loadModule('permisos/index.php?type=' + encodeURIComponent(event.target.value));
    }
    if (event.target.matches('[data-module-filter="permisos/index.php"] [name="id"]')) {
      loadModule('permisos/index.php?' + new URLSearchParams(new FormData(event.target.form)));
    }
    if (event.target.matches('#permissionsForm select')) {
      const select = event.target;
      const allowed = select.value === 'allow' || (select.value === 'inherit' && select.dataset.inherited === '1');
      const badge = select.closest('tr').querySelector('.effective-access');
      badge.classList.toggle('bg-success', allowed);
      badge.classList.toggle('bg-danger', !allowed);
      badge.textContent = (allowed ? 'Permitido' : 'Sin acceso') + (select.form.elements.type.value === 'role' || select.value === 'inherit' ? ' · Perfil' : ' · Usuario');
    }
  });
  document.addEventListener('submit', async event => {
    const form = event.target;
    if (form.matches('[data-delete-gmail]') && !window.confirm('¿Seguro que quieres eliminar el correo ' + form.dataset.deleteGmail + '?')) {
      event.preventDefault();
      return;
    }
    if (form.matches('[data-module-filter]')) {
      event.preventDefault();
      loadModule(form.dataset.moduleFilter + '?' + new URLSearchParams(new FormData(form)));
    }
    if (form.matches('#permissionsForm')) {
      event.preventDefault();
      const button = document.getElementById('savePermissions');
      const status = document.getElementById('permissionStatus');
      button.disabled = true; status.textContent = 'Guardando…';
      try {
        const response = await fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudieron guardar los permisos.');
        status.textContent = data.message;
        // Recargar aplica también los permisos nuevos al propio menú del administrador.
        rememberPermissions(new URLSearchParams({ type: form.elements.type.value, id: form.elements.id.value }));
        window.location.href = new URL('sistema/permisos', baseUrl);
      } catch (error) { status.textContent = error.message; button.disabled = false; }
    }
  });
  function initGmailTable() {
    const tbody = document.querySelector('#tablaGmail tbody');
    const rows = [...tbody.querySelectorAll('tr[data-token-active]')];
    const state = document.getElementById('estadoTokenGmail');
    let empty = tbody.querySelector('[data-gmail-empty]');
    if (!empty) {
      empty = document.createElement('tr');
      const cell = document.createElement('td'); cell.colSpan = 5;
      empty.append(cell); tbody.append(empty);
    }
    const search = document.getElementById('buscadorGmail');
    const size = document.getElementById('registrosPorPaginaGmail');
    const pages = document.getElementById('paginacionGmail');
    if (!search || !size || !pages) return;
    let current = 1;
    const render = () => {
      const filtered = rows.filter(row => (!state?.value || row.dataset.tokenActive === state.value) && row.cells[1].textContent.toLowerCase().includes(search.value.trim().toLowerCase()));
      empty.hidden = filtered.length > 0;
      empty.firstElementChild.textContent = rows.length ? 'No hay correos que coincidan con los filtros.' : 'No hay correos autorizados.';
      const count = Number(size.value); rows.forEach(row => row.hidden = true);
      filtered.slice((current - 1) * count, current * count).forEach(row => row.hidden = false);
      pages.replaceChildren();
      const total = Math.ceil(filtered.length / count);
      const summary=document.createElement('span');summary.className='small text-body-secondary';summary.textContent=filtered.length+' registros · Página '+current+' de '+Math.max(1,total);pages.append(summary);
      if (total <= 1) return;
      const start = Math.max(1, current - 2), end = Math.min(total, current + 2);
      [...new Set([1, ...Array.from({ length: end - start + 1 }, (_, i) => start + i), total])].forEach(number => {
        const btn = document.createElement('button'); btn.className = 'btn btn-sm btn-outline-primary pagination-button'; btn.textContent = number;
        btn.classList.toggle('is-active', number === current);
        btn.addEventListener('click', () => { current = number; render(); }); pages.append(btn);
      });
    };
    search.addEventListener('input', () => { current = 1; render(); });
    state?.addEventListener('change', () => { current = 1; render(); });
    size.addEventListener('change', () => { current = 1; render(); }); render();
  }
  function restoreLocation() {
    const params = new URLSearchParams(window.location.search);
    const slug = window.location.pathname.slice(baseUrl.pathname.length).replace(/^sistema\/?/, '').replace(/\/$/, '');
    const routeId = Object.keys(cleanRoutes).find(id => cleanRoutes[id] === slug);
    const legacy = params.get('modulo') === 'autoriza' ? 'Autoriza' : params.get('modulo');
    const requested = routeId || legacy || 'inicio';
    const target = document.getElementById(requested);
    if (!target?.matches('[data-module]')) {
      content.textContent = 'No tienes acceso a esta pantalla.';
      return;
    }
    if (requested === 'menuPermisos' && (params.has('type') || params.has('id'))) rememberPermissions(params);
    activate(target, false);
    const url = new URL('sistema/' + cleanRoutes[requested], baseUrl);
    // La selección se conserva en la pestaña, sin parámetros en la dirección.
    history.replaceState({}, '', url);

  }
  window.addEventListener('popstate', restoreLocation);
  document.addEventListener('DOMContentLoaded', restoreLocation);
})();