(() => {
  const overlay = document.getElementById('pageLoadingOverlay') || document.getElementById('loadingOverlay');
  const showOverlay = (msg) => {
    if (!overlay) return;
    const label = overlay.querySelector('[data-loading-msg]');
    if (label && msg) label.textContent = msg;
    overlay.classList.add('show');
    overlay.style.display = 'flex';
  };

  document.querySelectorAll('[data-autodismiss="true"]').forEach((el) => {
    const ms = Number(el.getAttribute('data-timeout')) || 4500;
    window.setTimeout(() => el.remove(), ms);
  });

  document.addEventListener('click', (e) => {
    const target = e.target.closest('[data-confirm]');
    if (!target) return;
    const msg = target.getAttribute('data-confirm') || 'Yakin?';
    if (!window.confirm(msg)) {
      e.preventDefault();
      e.stopPropagation();
    }
  }, true);

  document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.hasAttribute('data-loading')) {
      const msg = form.getAttribute('data-loading') || 'Memproses…';
      showOverlay(msg);
      form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((btn) => {
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
      });
    }
  }, true);
})();
