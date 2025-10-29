// Confirm delete actions
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-confirm]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      if (!confirm(btn.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });
});
