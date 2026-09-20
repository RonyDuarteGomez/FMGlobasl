// CSRF solamente para solicitudes AJAX del mismo origen. Nunca enviarlo a APIs externas.
function fmCsrfToken() { return document.querySelector('meta[name="csrf-token"]')?.content || ''; }
$.ajaxPrefilter(function (options, original, xhr) {
  const url = new URL(options.url, window.location.href);
  if (url.origin === window.location.origin && !/^(GET|HEAD|OPTIONS)$/i.test(options.type || 'GET')) {
    xhr.setRequestHeader('X-CSRF-Token', fmCsrfToken());
  }
});
$(document).ajaxError(function (event, xhr, settings) {
  if (new URL(settings.url, window.location.href).origin !== window.location.origin || xhr.status === 401) return;
  alert(xhr.responseJSON?.message || (xhr.responseText && !xhr.responseText.includes('<') ? xhr.responseText : 'No se pudo completar la solicitud.'));
});
