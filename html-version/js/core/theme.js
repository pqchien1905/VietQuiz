/**
 * VietQuiz Theme Module
 * Dark / Light / System mode — mirrors next-themes behavior
 */

const STORAGE_KEY = 'vietquiz-theme';

/**
 * Get system preference
 * @returns {'dark'|'light'}
 */
function getSystemTheme() {
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

/**
 * Get the stored theme preference
 * @returns {'dark'|'light'|'system'}
 */
export function getSavedTheme() {
  return localStorage.getItem(STORAGE_KEY) || 'system';
}

/**
 * Apply theme to document
 * @param {'dark'|'light'|'system'} theme
 */
export function applyTheme(theme) {
  const resolved = theme === 'system' ? getSystemTheme() : theme;
  document.documentElement.classList.toggle('dark', resolved === 'dark');
  document.documentElement.setAttribute('data-theme', resolved);

  // Update all theme toggle buttons
  document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
    const icon = btn.querySelector('[data-theme-icon]');
    if (icon) {
      icon.innerHTML = resolved === 'dark' ? getSunIcon() : getMoonIcon();
    }
    btn.setAttribute('aria-label', resolved === 'dark' ? 'Chuyển sang chế độ sáng' : 'Chuyển sang chế độ tối');
  });

  // Update theme select dropdowns
  document.querySelectorAll('[data-theme-select]').forEach(sel => {
    sel.value = theme;
  });
}

/**
 * Save and apply theme
 * @param {'dark'|'light'|'system'} theme
 */
export function setTheme(theme) {
  localStorage.setItem(STORAGE_KEY, theme);
  applyTheme(theme);
}

/**
 * Toggle between dark and light
 */
export function toggleTheme() {
  const current = getSavedTheme();
  const resolved = current === 'system' ? getSystemTheme() : current;
  setTheme(resolved === 'dark' ? 'light' : 'dark');
}

/**
 * Initialize theme on page load
 * Call this once in each page's JS
 */
export function initTheme() {
  applyTheme(getSavedTheme());

  // React to system changes
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (getSavedTheme() === 'system') {
      applyTheme('system');
    }
  });

  // Bind toggle buttons
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-theme-toggle]');
    if (btn) toggleTheme();
  });

  // Bind select dropdowns
  document.addEventListener('change', e => {
    const sel = e.target.closest('[data-theme-select]');
    if (sel) setTheme(sel.value);
  });
}

// Icons
function getMoonIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
}
function getSunIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`;
}

export default { initTheme, setTheme, toggleTheme, getSavedTheme, applyTheme };
