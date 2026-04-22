/**
 * VietQuiz Header Component
 * Renders the top header with search, theme toggle, notifications, and user menu
 */

import { t } from '../core/i18n.js';
import { getUserName, getRole, logout, getBasePath } from '../core/auth.js';
import { toggleTheme, getSavedTheme } from '../core/theme.js';
import { openMobileSidebar } from './sidebar.js';

const SEARCH_PLACEHOLDER = {
  teacher: 'search.teacherGeneral',
  student: 'search.general',
};

const SEARCH_TRANSLATIONS = {
  'search.teacherGeneral': 'Tìm kiếm bài kiểm tra, bài tập, học sinh...',
  'search.general': 'Tìm kiếm khóa học, bài kiểm tra...',
};

/**
 * Render header into a container
 * @param {HTMLElement} container
 * @param {{role?: string, notificationCount?: number, searchKey?: string}} opts
 */
export function renderHeader(container, opts = {}) {
  const role = opts.role || getRole() || 'teacher';
  const name = opts.name || getUserName();
  const notificationCount = opts.notificationCount ?? 3;
  const base = getBasePath();

  const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
  const searchPlaceholder = SEARCH_TRANSLATIONS[SEARCH_PLACEHOLDER[role]] || 'Tìm kiếm...';

  const isDark = document.documentElement.classList.contains('dark');

  container.innerHTML = `
    <header class="header" id="main-header">
      <!-- Mobile menu button -->
      <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="Mở menu" onclick="window.__vqOpenSidebar()">
        ${menuIcon()}
      </button>

      <!-- Search -->
      <div class="header-search">
        <div class="search-input-wrapper" style="position:relative;">
          ${searchIcon()}
          <input
            type="search"
            class="input"
            placeholder="${searchPlaceholder}"
            style="padding-left: 2.5rem;"
            aria-label="Tìm kiếm"
          />
        </div>
      </div>

      <!-- Right actions -->
      <div class="header-actions">
        <!-- Theme toggle -->
        <button
          class="icon-btn"
          data-theme-toggle
          aria-label="${isDark ? 'Chuyển sang chế độ sáng' : 'Chuyển sang chế độ tối'}"
          title="${isDark ? 'Chế độ sáng' : 'Chế độ tối'}"
        >
          <span data-theme-icon>${isDark ? sunIcon() : moonIcon()}</span>
        </button>

        <!-- Notifications -->
        <a href="${base}${role}/notifications.html" class="icon-btn notification-btn" aria-label="Thông báo" style="position:relative; text-decoration:none; color: inherit;">
          ${bellIcon()}
          ${notificationCount > 0 ? `
            <span class="badge-dot-indicator">${notificationCount > 9 ? '9+' : notificationCount}</span>
          ` : ''}
        </a>

        <!-- User dropdown -->
        <div class="dropdown" id="user-dropdown">
          <button class="user-menu-btn" id="user-menu-trigger" aria-haspopup="true" aria-expanded="false">
            <div class="avatar avatar-md" style="background-color: var(--primary); color: var(--primary-foreground); font-size: var(--text-sm); font-weight: 600;">
              ${initials}
            </div>
            <div class="user-menu-info" style="display: flex; flex-direction: column; align-items: flex-start;">
              <span class="user-menu-name">${name}</span>
              <span class="user-menu-role">${role === 'teacher' ? t('common.teacher') : t('common.student')}</span>
            </div>
            <span style="color: var(--muted-foreground); margin-left: 0.25rem;">${chevronIcon()}</span>
          </button>

          <div class="dropdown-menu" id="user-menu" role="menu">
            <div class="dropdown-label">${t('common.myAccount')}</div>
            <a href="${base}${role}/vip.html" class="dropdown-item" style="color: #eab308;" role="menuitem">
              ${crownIcon()}
              ${t('nav.vip')}
            </a>
            <div class="dropdown-separator"></div>
            <a href="${base}${role}/profile.html" class="dropdown-item" role="menuitem">
              ${userIcon()} ${t('common.profile')}
            </a>
            <a href="${base}${role}/settings.html" class="dropdown-item" role="menuitem">
              ${settingsIcon()} ${t('common.settings')}
            </a>
            <a href="${base}${role}/help.html" class="dropdown-item" role="menuitem">
              ${helpIcon()} ${t('common.helpSupport')}
            </a>
            <div class="dropdown-separator"></div>
            <button class="dropdown-item danger" onclick="window.__vqLogout()" role="menuitem">
              ${logoutIcon()} ${t('common.logOut')}
            </button>
          </div>
        </div>
      </div>
    </header>
  `;

  // Expose globals
  window.__vqLogout = logout;
  window.__vqOpenSidebar = openMobileSidebar;

  // Dropdown toggle
  const trigger = container.querySelector('#user-menu-trigger');
  const menu    = container.querySelector('#user-menu');
  if (trigger && menu) {
    trigger.addEventListener('click', e => {
      e.stopPropagation();
      const open = menu.classList.toggle('open');
      trigger.setAttribute('aria-expanded', open);
    });
    document.addEventListener('click', () => {
      menu.classList.remove('open');
      trigger.setAttribute('aria-expanded', false);
    });
  }
}

// ----- Icon helpers -----
function menuIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>`;
}
function searchIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--muted-foreground);pointer-events:none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>`;
}
function moonIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
}
function sunIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`;
}
function bellIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>`;
}
function chevronIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>`;
}
function crownIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/></svg>`;
}
function userIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`;
}
function settingsIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>`;
}
function helpIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`;
}
function logoutIcon() {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>`;
}

export default { renderHeader };
