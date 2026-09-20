(async()=>{
 const results=[];const check=(name,ok)=>{results.push({name,ok:!!ok});if(!ok)throw new Error(name);};
 let calls=0,payload,fail=false;
 window.fetch=async(url,options)=>{calls++;payload=options.body;return {ok:!fail,json:async()=>fail?{message:'Otra persona modificó la programación.'}:{success:true,revision:1,active:true,message:'Programación guardada.'}};};
 try {
  iniciarActivacion();
  const form=document.getElementById('formActivacion'),rows=[...form.querySelectorAll('[data-day]')],first=rows[0],mode=first.querySelector('[data-mode]');
  const change=el=>el.dispatchEvent(new Event('change',{bubbles:true}));
  mode.value='hours';change(mode);check('Modo horario muestra un intervalo',!first.querySelector('.schedule-hours').hidden&&first.querySelectorAll('.schedule-slot').length===1);
  first.querySelector('[data-add-slot]').click();
  form.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));check('No envía períodos superpuestos',calls===0&&document.getElementById('scheduleStatus').textContent.includes('sin superposiciones'));
  const slots=first.querySelectorAll('.schedule-slot');slots[0].querySelector('[data-end]').value='12:00';slots[1].querySelector('[data-start]').value='15:00';slots[1].querySelector('[data-end]').value='24:00';
  first.querySelector('[data-copy-day]').click();check('Copia los dos períodos a los siete días',rows.every(row=>row.querySelectorAll('.schedule-slot').length===2&&row.querySelector('[data-mode]').value==='hours'));
  const last=rows[6];last.querySelector('[data-mode]').value='off';change(last.querySelector('[data-mode]'));
  check('Día deshabilitado oculta y desactiva horarios',last.querySelector('.schedule-hours').hidden&&last.querySelector('[data-start]').disabled);
  form.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));await new Promise(r=>setTimeout(r,100));
  const week=JSON.parse(payload.get('schedule'));check('Guarda semana completa y final de día',calls===1&&Object.keys(week).length===7&&week[1].slots[1].end==='24:00'&&week[7].slots.length===0);
  check('Actualiza revisión tras guardar',form.dataset.revision==='1');
  first.querySelector('[data-remove-slot]').click();check('Quita un intervalo',first.querySelectorAll('.schedule-slot').length===1);
  fail=true;form.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));await new Promise(r=>setTimeout(r,100));
  check('Conflicto conserva edición y revisión',form.dataset.revision==='1'&&first.querySelectorAll('.schedule-slot').length===1&&document.getElementById('scheduleStatus').textContent.includes('Otra persona'));
  check('Pantalla sin desbordamiento horizontal',document.documentElement.scrollWidth<=innerWidth+1);
  check('Botón de guardado accesible',form.querySelector('[type=submit]').getBoundingClientRect().bottom<=innerHeight);
  document.title='WEEKLY_UI_PASS';
 } catch(error){results.push({name:error.message,ok:false});document.title='WEEKLY_UI_FAIL';}
 const pre=document.createElement('pre');pre.id='weeklyResults';pre.textContent=JSON.stringify(results);document.body.append(pre);
})();