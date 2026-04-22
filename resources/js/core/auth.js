/**
 * VietQuiz Auth Module
 * Cookie-based authentication — mirrors the Next.js middleware logic
 */

const COOKIE_ROLE = 'auth_role';
const COOKIE_NAME = 'auth_name';

// ---------- Cookie utilities ----------

function setCookie(name, value, days = 7) {
  const expires = new Date();
  expires.setDate(expires.getDate() + days);
  document.cookie = `${name}=${encodeURIComponent(value)}; path=/; expires=${expires.toUTCString()}; SameSite=Lax`;
}

function getCookie(name) {
  const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  return match ? decodeURIComponent(match[2]) : null;
}

function deleteCookie(name) {
  document.cookie = `${name}=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax`;
}

// ---------- Auth helpers ----------

/**
 * Get current user role
 * @returns {'teacher'|'student'|null}
 */
export function getRole() {
  return getCookie(COOKIE_ROLE);
}

/**
 * Get current user display name
 * @returns {string}
 */
export function getUserName() {
  return getCookie(COOKIE_NAME) || 'Người dùng';
}

/**
 * Check if user is authenticated
 * @returns {boolean}
 */
export function isAuthenticated() {
  return Boolean(getCookie(COOKIE_ROLE));
}

/**
 * Login — simulate API, set cookies, redirect
 * @param {'teacher'|'student'} role
 * @param {string} email
 * @param {string} password
 * @param {boolean} remember
 * @returns {Promise<{success: boolean, error?: string}>}
 */
export async function login(role, email, password, remember = false) {
  // Simulate network request
  await delay(1200);

  // Demo: accept any credentials
  if (!email || !password) {
    return { success: false, error: 'Vui lòng nhập email và mật khẩu.' };
  }
  if (password.length < 6) {
    return { success: false, error: 'Mật khẩu phải có ít nhất 6 ký tự.' };
  }

  const name = role === 'teacher' ? 'Giáo viên Demo' : 'Học sinh Demo';
  const days = remember ? 30 : 7;

  setCookie(COOKIE_ROLE, role, days);
  setCookie(COOKIE_NAME, name, days);

  return { success: true };
}

/**
 * Logout — clear cookies and redirect to login
 */
export function logout() {
  deleteCookie(COOKIE_ROLE);
  deleteCookie(COOKIE_NAME);
  window.location.href = getBasePath() + 'login.html';
}

/**
 * Register — simulate API call
 */
export async function register(role, data) {
  await delay(1500);
  if (!data.name || !data.email || !data.password) {
    return { success: false, error: 'Vui lòng điền đầy đủ thông tin.' };
  }
  return { success: true };
}

// ---------- Route Guards ----------

/**
 * Guard for teacher pages — call at top of each teacher page
 * Redirects to login if not authenticated or wrong role
 */
export function requireTeacher() {
  const role = getRole();
  if (!role) {
    window.location.href = getBasePath() + 'login.html?from=' + encodeURIComponent(window.location.pathname);
    return false;
  }
  if (role !== 'teacher') {
    deleteCookie(COOKIE_ROLE);
    deleteCookie(COOKIE_NAME);
    window.location.href = getBasePath() + 'login.html';
    return false;
  }
  return true;
}

/**
 * Guard for student pages — call at top of each student page
 */
export function requireStudent() {
  const role = getRole();
  if (!role) {
    window.location.href = getBasePath() + 'login.html?from=' + encodeURIComponent(window.location.pathname);
    return false;
  }
  if (role !== 'student') {
    deleteCookie(COOKIE_ROLE);
    deleteCookie(COOKIE_NAME);
    window.location.href = getBasePath() + 'login.html';
    return false;
  }
  return true;
}

/**
 * Redirect authenticated users away from auth pages
 */
export function redirectIfAuthenticated() {
  const role = getRole();
  if (role === 'teacher') {
    window.location.href = getBasePath() + 'teacher/dashboard.html';
  } else if (role === 'student') {
    window.location.href = getBasePath() + 'student/dashboard.html';
  }
}

// ---------- Utilities ----------

/**
 * Get relative base path (handles pages in subdirectories)
 */
export function getBasePath() {
  const depth = window.location.pathname.split('/').filter(Boolean).length;
  // If we're in a subfolder like teacher/ or student/, go up one level
  const inSubfolder = window.location.pathname.includes('/teacher/') || window.location.pathname.includes('/student/');
  return inSubfolder ? '../' : './';
}

function delay(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

export default { getRole, getUserName, isAuthenticated, login, logout, register, requireTeacher, requireStudent, redirectIfAuthenticated, getBasePath };
