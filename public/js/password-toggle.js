document.addEventListener('DOMContentLoaded', () => {
  if (window.lucide && typeof window.lucide.createIcons === 'function') {
    window.lucide.createIcons();
  }

  const toggles = document.querySelectorAll('[data-password-toggle]');

  toggles.forEach((button) => {
    button.addEventListener('click', () => {
      const field = button.closest('.password-field');
      const input = field ? field.querySelector('input[type="password"], input[type="text"]') : null;

      if (!input) {
        return;
      }

      const shouldShow = input.type === 'password';
      input.type = shouldShow ? 'text' : 'password';
      button.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
      button.setAttribute('aria-label', shouldShow ? 'Sembunyikan password' : 'Tampilkan password');

      const icon = button.querySelector('i');
      if (icon) {
        icon.setAttribute('data-lucide', shouldShow ? 'eye-off' : 'eye');
      }

      if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
      }
    });
  });
});
