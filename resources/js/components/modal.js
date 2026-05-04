/**
 * VietQuiz Modal / Dialog Component
 * Pure JS replacement for Radix Dialog
 */

/**
 * Open a modal by ID
 * @param {string} modalId - The ID of the modal element
 */
export function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) { console.warn(`Modal #${modalId} not found`); return; }
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
  modal.querySelector('[data-modal-close]')?.focus();
}

/**
 * Close a modal by ID
 * @param {string} modalId - The ID of the modal element
 */
export function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;
  modal.classList.remove('open');
  document.body.style.overflow = '';
}

/**
 * Close all open modals
 */
export function closeAllModals() {
  document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
  document.body.style.overflow = '';
}

function ensureAppDialog() {
  let overlay = document.getElementById('app-dialog');
  if (overlay) return overlay;

  overlay = document.createElement('div');
  overlay.id = 'app-dialog';
  overlay.className = 'modal-overlay app-dialog-overlay';
  overlay.innerHTML = `
    <div class="modal app-dialog" role="dialog" aria-modal="true" aria-labelledby="app-dialog-title">
      <div class="modal-header">
        <div>
          <h2 class="modal-title" id="app-dialog-title">Xác nhận</h2>
          <p class="modal-desc" id="app-dialog-message"></p>
        </div>
        <button type="button" class="modal-close" data-app-dialog-cancel aria-label="Đóng">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" data-app-dialog-cancel>Hủy</button>
        <button type="button" class="btn btn-primary" data-app-dialog-confirm>OK</button>
      </div>
    </div>
  `;
  document.body.appendChild(overlay);

  return overlay;
}

function showDialog({ title = 'Thông báo', message = '', confirmText = 'OK', cancelText = 'Hủy', showCancel = false, destructive = false } = {}) {
  const overlay = ensureAppDialog();
  const titleEl = overlay.querySelector('#app-dialog-title');
  const messageEl = overlay.querySelector('#app-dialog-message');
  const confirmBtn = overlay.querySelector('[data-app-dialog-confirm]');
  const cancelBtns = overlay.querySelectorAll('[data-app-dialog-cancel]');
  const cancelBtn = overlay.querySelector('.modal-footer [data-app-dialog-cancel]');

  titleEl.textContent = title;
  messageEl.textContent = message;
  confirmBtn.textContent = confirmText;
  confirmBtn.className = destructive ? 'btn btn-destructive' : 'btn btn-primary';
  cancelBtn.textContent = cancelText;
  cancelBtn.style.display = showCancel ? '' : 'none';

  return new Promise(resolve => {
    const cleanup = result => {
      overlay.classList.remove('open');
      document.body.style.overflow = '';
      confirmBtn.removeEventListener('click', onConfirm);
      cancelBtns.forEach(btn => btn.removeEventListener('click', onCancel));
      overlay.removeEventListener('click', onBackdrop);
      document.removeEventListener('keydown', onKeydown);
      resolve(result);
    };
    const onConfirm = () => cleanup(true);
    const onCancel = () => cleanup(false);
    const onBackdrop = e => {
      if (e.target === overlay) cleanup(false);
    };
    const onKeydown = e => {
      if (e.key === 'Escape') cleanup(false);
      if (e.key === 'Enter') cleanup(true);
    };

    confirmBtn.addEventListener('click', onConfirm);
    cancelBtns.forEach(btn => btn.addEventListener('click', onCancel));
    overlay.addEventListener('click', onBackdrop);
    document.addEventListener('keydown', onKeydown);

    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => (showCancel ? cancelBtn : confirmBtn).focus(), 0);
  });
}

export function showAppAlert(message, options = {}) {
  return showDialog({
    title: options.title || 'Thông báo',
    message,
    confirmText: options.confirmText || 'OK',
    showCancel: false,
  });
}

export function showAppConfirm(message, options = {}) {
  return showDialog({
    title: options.title || 'Xác nhận',
    message,
    confirmText: options.confirmText || 'OK',
    cancelText: options.cancelText || 'Hủy',
    showCancel: true,
    destructive: Boolean(options.destructive),
  });
}

function initConfirmSubmits(root = document) {
  root.addEventListener('submit', async event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed === '1') {
      return;
    }

    event.preventDefault();
    const accepted = await showAppConfirm(form.dataset.confirm, {
      title: form.dataset.confirmTitle || 'Xác nhận',
      confirmText: form.dataset.confirmOk || 'OK',
      cancelText: form.dataset.confirmCancel || 'Hủy',
      destructive: form.dataset.confirmDestructive !== 'false',
    });

    if (!accepted) return;
    form.dataset.confirmed = '1';
    HTMLFormElement.prototype.submit.call(form);
  });
}

/**
 * Initialize modal event listeners on a page.
 * Call once per page after DOM is ready.
 * Handles: [data-modal-open], [data-modal-close], overlay backdrop click, Escape key.
 * @param {string|Element} root - Root element or selector to search within
 */
export function initModals(root = document) {
  const el = typeof root === 'string' ? document.querySelector(root) : root;
  if (!el) return;

  // Open buttons: <button data-modal-open="my-modal">
  el.querySelectorAll('[data-modal-open]').forEach(btn => {
    btn.addEventListener('click', () => {
      openModal(btn.dataset.modalOpen);
    });
  });

  // Close buttons: <button data-modal-close> or <button data-modal-close="my-modal">
  el.querySelectorAll('[data-modal-close]').forEach(btn => {
    btn.addEventListener('click', () => {
      const modalId = btn.dataset.modalClose || btn.closest('.modal-overlay')?.id;
      if (modalId) closeModal(modalId);
    });
  });

  // Overlay backdrop click → close
  el.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
      if (e.target === overlay) {
        closeModal(overlay.id);
      }
    });
  });
}

/**
 * Global keyboard handler (Escape to close)
 */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeAllModals();
});

window.showAppAlert = showAppAlert;
window.showAppConfirm = showAppConfirm;
window.alert = message => {
  showAppAlert(String(message ?? ''));
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => initConfirmSubmits(document));
} else {
  initConfirmSubmits(document);
}

/* =========================================================
   Standard Modal HTML Structure
   =========================================================

   <!-- Trigger -->
   <button class="btn btn-primary" data-modal-open="my-modal">Open Modal</button>

   <!-- Modal -->
   <div class="modal-overlay" id="my-modal">
     <div class="modal" role="dialog" aria-modal="true" aria-labelledby="my-modal-title">
       <div class="modal-header">
         <div>
           <h2 class="modal-title" id="my-modal-title">Modal Title</h2>
           <p class="modal-desc">Optional description</p>
         </div>
         <button class="modal-close" data-modal-close aria-label="Đóng">
           <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
         </button>
       </div>
       <div class="modal-body">
         ...content...
       </div>
       <div class="modal-footer">
         <button class="btn btn-ghost" data-modal-close>Hủy</button>
         <button class="btn btn-primary" onclick="handleSave()">Lưu</button>
       </div>
     </div>
   </div>

   ========================================================= */

export default { openModal, closeModal, closeAllModals, initModals, showAppAlert, showAppConfirm };
