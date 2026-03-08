(function (window) {
  function applyCsrfAjaxSetup(maxRetries) {
    var retries = 0;
    var limit = typeof maxRetries === 'number' ? maxRetries : 0;
    function setup() {
      if (window.jQuery && window.CSRF_TOKEN) {
        window.jQuery.ajaxSetup({headers: {'X-CSRF-Token': window.CSRF_TOKEN}});
        return;
      }
      if (retries < limit) {
        retries++;
        window.setTimeout(setup, 100);
      }
    }
    setup();
  }

  function submitLogoutForm(action) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = action;
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'csrf_token';
    input.value = window.CSRF_TOKEN || '';
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
  }

  window.applyCsrfAjaxSetup = applyCsrfAjaxSetup;
  window.submitLogoutForm = submitLogoutForm;
})(window);
