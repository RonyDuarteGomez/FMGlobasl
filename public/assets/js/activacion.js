function iniciarActivacion() {
  const form = document.getElementById('formActivacion');
  if (!form || form.dataset.bound) return;
  form.dataset.bound = '1';
  const status = document.getElementById('scheduleStatus');
  const template = document.getElementById('scheduleSlotTemplate');
  let saving = false;
  const dirty = () => { status.classList.remove('text-danger','text-success'); status.textContent = 'Cambios pendientes de guardar.'; };
  const slots = row => [...row.querySelectorAll('.schedule-slot')].map(slot => ({start: slot.querySelector('[data-start]').value, end: slot.querySelector('[data-end]').value}));
  const appendSlot = (row, range = {start:'09:00',end:'18:00'}) => {
    const node = template.content.firstElementChild.cloneNode(true);
    for (const key of ['start','end']) {
      const select = node.querySelector('[data-'+key+']');
      if (![...select.options].some(o=>o.value===range[key])) select.add(new Option(range[key],range[key]));
      select.value = range[key];
    }
    row.querySelector('.schedule-slots').append(node);
  };
  const refresh = row => {
    const mode = row.querySelector('[data-mode]').value;
    row.querySelector('.schedule-hours').hidden = mode !== 'hours';
    const summary = row.querySelector('.schedule-day-summary');
    summary.hidden = mode === 'hours';
    summary.textContent = mode === 'all' ? 'Disponible las 24 horas' : 'Sin atención este día';
    if (mode === 'hours' && !slots(row).length) appendSlot(row);
    row.querySelectorAll('[data-start],[data-end]').forEach(select => { select.disabled = mode !== 'hours'; });
    row.querySelector('[data-add-slot]').disabled = slots(row).length >= 6;
  };
  form.querySelectorAll('[data-day]').forEach(refresh);
  form.addEventListener('change', event => {
    if (saving) return;
    const row = event.target.closest('[data-day]');
    if (row) { refresh(row); dirty(); }
  });
  form.addEventListener('click', event => {
    if (saving) return;
    const button = event.target.closest('button');
    const row = button?.closest('[data-day]');
    if (!row) return;
    if (button.matches('[data-add-slot]')) {
      if (slots(row).length < 6) { appendSlot(row); refresh(row); dirty(); }
    } else if (button.matches('[data-remove-slot]')) {
      button.closest('.schedule-slot').remove();
      row.querySelector('[data-add-slot]').disabled = false;
      dirty();
    } else if (button.matches('[data-copy-day]')) {
      const mode = row.querySelector('[data-mode]').value;
      const ranges = slots(row);
      form.querySelectorAll('[data-day]').forEach(other => {
        if (other === row) return;
        other.querySelector('[data-mode]').value = mode;
        other.querySelector('.schedule-slots').replaceChildren();
        ranges.forEach(range => appendSlot(other,range));
        refresh(other);
      });
      dirty(); status.textContent = 'Horario copiado al resto de días. Revisa la semana y guarda los cambios.';
    }
  });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (saving) return;
    const week = {};
    for (const row of form.querySelectorAll('[data-day]')) {
      const mode = row.querySelector('[data-mode]').value;
      const ranges = mode === 'hours' ? slots(row).sort((a,b)=>a.start.localeCompare(b.start)) : [];
      let end = '';
      if (mode === 'hours' && (!ranges.length || ranges.some(range => {
        const invalid = range.start >= range.end || range.start < end; end = range.end; return invalid;
      }))) {
        status.classList.add('text-danger');status.textContent = 'Revisa '+row.querySelector('h4').textContent+': agrega horarios válidos, sin superposiciones.';
        row.querySelector('[data-mode]').focus();return;
      }
      week[row.dataset.day] = {mode,slots:ranges};
    }
    const body = new FormData(); body.set('schedule',JSON.stringify(week));body.set('revision',form.dataset.revision);
    saving = true;
    const controls = [...form.querySelectorAll('button,select')].map(el=>({el,disabled:el.disabled}));
    controls.forEach(({el})=>el.disabled=true);
    status.classList.remove('text-danger','text-success');status.textContent = 'Guardando programación…';
    try {
      const response = await fetch('activacion/actualizar_activacion.php', {method:'POST',credentials:'same-origin',headers:{'X-CSRF-Token':fmCsrfToken()},body});
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'No se pudo guardar la programación.');
      form.dataset.revision = result.revision;
      status.classList.add('text-success');status.textContent = result.message;
      const live = document.getElementById('scheduleLive');
      live.classList.toggle('text-bg-success',result.active);live.classList.toggle('text-bg-danger',!result.active);live.textContent = result.active?'Activo':'Inactivo';
    } catch (error) { status.classList.add('text-danger');status.textContent = error.message; }
    finally { saving = false; controls.forEach(({el,disabled})=>el.disabled=disabled); }
  });
}