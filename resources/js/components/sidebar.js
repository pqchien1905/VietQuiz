/**
 * VietQuiz Sidebar Component
 * Renders sidebar navigation for teacher or student
 */

import { t } from '../core/i18n.js';
import { getUserName, getRole, logout, getBasePath } from '../core/auth.js';

// ---------- SVG Icon Library ----------
const ICONS = {
  dashboard:   `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>`,
  bookOpen:    `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>`,
  fileQuestion:`<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><path d="M10 10.3c.2-.4.5-.8.9-1a2.1 2.1 0 0 1 2.6.4c.3.4.5.8.5 1.3 0 1.3-2 2-2 2"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
  library:     `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 6 4 14"/><path d="M12 6v14"/><path d="M8 8v12"/><path d="M4 4v16"/></svg>`,
  clipboard:   `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="15" y2="16"/><line x1="9" y1="8" x2="11" y2="8"/></svg>`,
  graduation:  `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>`,
  users:       `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>`,
  barChart:    `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>`,
  layers:      `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>`,
  award:       `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg>`,
  userPlus:    `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>`,
  bell:        `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>`,
  trash:       `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>`,
  crown:       `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/></svg>`,
  logout:      `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>`,
};

// ---------- Nav configs ----------
const TEACHER_NAV = [
  { key: 'nav.dashboard',   href: 'dashboard.html',   icon: 'dashboard'   },
  { key: 'nav.myClasses',   href: 'classes.html',     icon: 'bookOpen'    },
  { key: 'nav.courses',     href: 'courses.html',     icon: 'layers'      },
  { key: 'nav.quizzes',     href: 'quizzes.html',     icon: 'fileQuestion'},
  { key: 'nav.questionBank',href: 'questions.html',   icon: 'library'     },
  { key: 'nav.assignments', href: 'assignments.html', icon: 'clipboard'   },
  { key: 'nav.grading',     href: 'grading.html',     icon: 'graduation'  },
  { key: 'nav.students',    href: 'students.html',    icon: 'users'       },
  { key: 'nav.analytics',   href: 'analytics.html',   icon: 'barChart'    },
];

const STUDENT_NAV = [
  { key: 'nav.dashboard',   href: 'dashboard.html',  icon: 'dashboard'   },
  { key: 'nav.courses',     href: 'courses.html',    icon: 'bookOpen'    },
  { key: 'nav.quizzes',     href: 'quizzes.html',    icon: 'fileQuestion'},
  { key: 'nav.assignments', href: 'assignments.html',icon: 'clipboard'   },
  { key: 'nav.grades',      href: 'grades.html',     icon: 'award'       },
  { key: 'nav.joinClass',   href: 'join-class.html', icon: 'userPlus'    },
];

/**
 * Render sidebar into a container element
 * @param {HTMLElement} container - element to inject sidebar into
 * @param {'teacher'|'student'} role
 */
export function renderSidebar(container, role) {
  const navItems = role === 'teacher' ? TEACHER_NAV : STUDENT_NAV;
  const currentPage = window.location.pathname.split('/').pop() || 'dashboard.html';
  const base = getBasePath();

  const navHTML = navItems.map(item => {
    const isActive = currentPage === item.href || currentPage.startsWith(item.href.replace('.html', ''));
    return `
      <a href="${base}${role}/${item.href}" class="nav-item ${isActive ? 'active' : ''}" data-page="${item.href}">
        ${ICONS[item.icon]}
        <span>${t(item.key)}</span>
      </a>
    `;
  }).join('');

  const portalKey = role === 'teacher' ? 'common.teacherPortal' : 'common.studentPortal';

  container.innerHTML = `
    <aside class="sidebar" id="main-sidebar">
      <!-- Logo -->
      <a href="${base}index.html" class="sidebar-logo" aria-label="VietQuiz Home">
        <div class="sidebar-logo-icon">
          ${ICONS.graduation}
        </div>
        <div class="sidebar-logo-text">
          <h1>VietQuiz</h1>
          <p>${t(portalKey)}</p>
        </div>
      </a>

      <!-- Nav -->
      <nav class="sidebar-nav" aria-label="Điều hướng chính">
        ${navHTML}
      </nav>

      <!-- Bottom -->
      <div class="sidebar-bottom">
        <a href="${base}${role}/notifications.html" class="nav-item ${currentPage === 'notifications.html' ? 'active' : ''}">
          ${ICONS.bell}
          <span>${t('nav.notifications')}</span>
        </a>
        <a href="${base}${role}/trash.html" class="nav-item ${currentPage === 'trash.html' ? 'active' : ''}">
          ${ICONS.trash}
          <span>${t('nav.trash')}</span>
        </a>
        <button class="nav-item" id="sidebar-logout-btn" onclick="window.__vqLogout()">
          ${ICONS.logout}
          <span>${t('nav.logout')}</span>
        </button>
      </div>
    </aside>

    <!-- Mobile overlay -->
    <div class="mobile-overlay" id="mobile-overlay"></div>
  `;

  // Expose logout globally for onclick
  window.__vqLogout = logout;

  // Mobile overlay click
  const overlay = container.querySelector('#mobile-overlay');
  if (overlay) overlay.addEventListener('click', closeMobileSidebar);
}

export function openMobileSidebar() {
  const sidebar = document.getElementById('main-sidebar');
  const overlay = document.getElementById('mobile-overlay');
  if (sidebar) sidebar.classList.add('mobile-open');
  if (overlay) overlay.classList.add('open');
  document.body.style.overflow = 'hidden';
}

export function closeMobileSidebar() {
  const sidebar = document.getElementById('main-sidebar');
  const overlay = document.getElementById('mobile-overlay');
  if (sidebar) sidebar.classList.remove('mobile-open');
  if (overlay) overlay.classList.remove('open');
  document.body.style.overflow = '';
}

export default { renderSidebar, ICONS, openMobileSidebar, closeMobileSidebar };
