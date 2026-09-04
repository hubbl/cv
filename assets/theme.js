const root = document.documentElement;
const buttons = document.querySelectorAll('[data-theme-value]');
const themeColor = document.querySelector('meta[name="theme-color"]');
const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');

function storedTheme() {
  try {
    const value = localStorage.getItem('theme');
    return value === 'light' || value === 'dark' ? value : 'system';
  } catch {
    return 'system';
  }
}

function resolvedTheme(value) {
  return value === 'system' ? (systemTheme.matches ? 'dark' : 'light') : value;
}

function updateInterface(value) {
  buttons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.themeValue === value)));
  if (themeColor) themeColor.content = resolvedTheme(value) === 'dark' ? '#202421' : '#f3f0e8';
}

function setTheme(value) {
  if (value === 'system') root.removeAttribute('data-theme');
  else root.dataset.theme = value;

  try {
    if (value === 'system') localStorage.removeItem('theme');
    else localStorage.setItem('theme', value);
  } catch {
    // The selected theme still applies for this page if storage is unavailable.
  }

  updateInterface(value);
}

buttons.forEach((button) => button.addEventListener('click', () => setTheme(button.dataset.themeValue)));
systemTheme.addEventListener('change', () => {
  if (storedTheme() === 'system') updateInterface('system');
});
updateInterface(storedTheme());
