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

export default { openModal, closeModal, closeAllModals, initModals };
