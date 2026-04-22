/**
 * VietQuiz — Shared page initializer for dashboard pages
 * Import this in each teacher/student page
 */

import { initTheme } from '../core/theme.js';
import { renderSidebar } from './sidebar.js';
import { renderHeader } from './header.js';
import { getUserName, getRole, getBasePath } from '../core/auth.js';

/**
 * Initialize a dashboard page (sidebar + header + theme)
 * @param {'teacher'|'student'} expectedRole - role for this page
 * @param {object} opts
 * @param {number} opts.notificationCount
 */
export function initDashboardPage(expectedRole, opts = {}) {
  // Apply theme immediately (prevents flash)
  initTheme();

  // Auth guard
  const role = getRole();
  const base = getBasePath();
  if (!role) {
    window.location.href = base + 'login.html';
    return false;
  }
  if (expectedRole && role !== expectedRole) {
    window.location.href = base + 'login.html';
    return false;
  }

  // Render layout
  const sidebarSlot = document.getElementById('sidebar-slot');
  const headerSlot  = document.getElementById('header-slot');

  if (sidebarSlot) renderSidebar(sidebarSlot, role);
  if (headerSlot)  renderHeader(headerSlot, {
    role,
    name: getUserName(),
    notificationCount: opts.notificationCount ?? 3,
  });

  // Mark page as loaded
  document.body.classList.add('page-loaded', 'page-enter');
  return true;
}

export default { initDashboardPage };
