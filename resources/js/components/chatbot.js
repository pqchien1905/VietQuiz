function createMessageElement(type, text, actions) {
  const item = document.createElement('div');
  item.className = `vq-chatbot__message vq-chatbot__message--${type}`;

  const bubble = document.createElement('div');
  bubble.className = 'vq-chatbot__bubble';
  bubble.textContent = text;
  item.appendChild(bubble);

  if (Array.isArray(actions) && actions.length) {
    const actionWrap = document.createElement('div');
    actionWrap.className = 'vq-chatbot__actions';

    actions.forEach((action) => {
      if (!action || !action.url || !action.label) return;
      const link = document.createElement('a');
      link.href = action.url;
      link.textContent = action.label;
      actionWrap.appendChild(link);
    });

    if (actionWrap.children.length) {
      item.appendChild(actionWrap);
    }
  }

  return item;
}

function autosize(textarea) {
  textarea.style.height = 'auto';
  textarea.style.height = `${Math.min(textarea.scrollHeight, 96)}px`;
}

function initChatbot(widget) {
  const panel = widget.querySelector('[data-chatbot-panel]');
  const toggle = widget.querySelector('[data-chatbot-toggle]');
  const minimize = widget.querySelector('[data-chatbot-minimize]');
  const form = widget.querySelector('[data-chatbot-form]');
  const input = widget.querySelector('[data-chatbot-input]');
  const messages = widget.querySelector('[data-chatbot-messages]');
  const endpoint = widget.dataset.endpoint;
  const role = widget.dataset.role || 'student';
  const storageKey = `vietquiz-chatbot-open-${widget.dataset.userId || 'guest'}-${role}`;
  let pending = false;

  function scrollToBottom() {
    messages.scrollTop = messages.scrollHeight;
  }

  function addMessage(type, text, actions = []) {
    messages.appendChild(createMessageElement(type, text, actions));
    scrollToBottom();
  }

  function setOpen(open) {
    widget.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    panel.setAttribute('aria-hidden', open ? 'false' : 'true');
    localStorage.setItem(storageKey, open ? '1' : '0');
    if (open) {
      window.setTimeout(() => input.focus(), 120);
    }
  }

  function addTyping() {
    const item = document.createElement('div');
    item.className = 'vq-chatbot__message vq-chatbot__message--bot vq-chatbot__typing';
    item.dataset.typing = 'true';
    item.innerHTML = '<div class="vq-chatbot__bubble"><span></span><span></span><span></span></div>';
    messages.appendChild(item);
    scrollToBottom();
  }

  function removeTyping() {
    messages.querySelector('[data-typing="true"]')?.remove();
  }

  async function sendMessage(text) {
    const message = text.trim();
    if (!message || pending) return;

    pending = true;
    addMessage('user', message);
    input.value = '';
    autosize(input);
    addTyping();

    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({ message }),
      });

      if (!response.ok) {
        throw new Error('Request failed');
      }

      const data = await response.json();
      removeTyping();
      addMessage('bot', data.reply || 'Mình chưa xử lý được câu hỏi này.', data.actions || []);
    } catch (error) {
      removeTyping();
      addMessage('bot', 'Hiện chatbot chưa gửi được câu hỏi. Bạn có thể mở Trung tâm trợ giúp để gửi ticket cho đội hỗ trợ.', []);
    } finally {
      pending = false;
    }
  }

  if (!messages.children.length) {
    const greeting = role === 'teacher'
      ? 'Chào thầy/cô, mình có thể hỗ trợ tạo lớp, quiz, bài tập, chấm điểm, học sinh, báo cáo và VIP.'
      : 'Chào bạn, mình có thể hỗ trợ làm quiz, nộp bài tập, xem điểm, tham gia lớp, tài khoản và VIP.';
    addMessage('bot', greeting);
  }

  toggle.addEventListener('click', () => setOpen(!widget.classList.contains('is-open')));
  minimize.addEventListener('click', () => setOpen(false));

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    sendMessage(input.value);
  });

  input.addEventListener('input', () => autosize(input));
  input.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      form.requestSubmit();
    }
  });

  widget.querySelectorAll('[data-chatbot-prompt]').forEach((button) => {
    button.addEventListener('click', () => {
      setOpen(true);
      sendMessage(button.dataset.chatbotPrompt || button.textContent || '');
    });
  });

  if (localStorage.getItem(storageKey) === '1') {
    setOpen(true);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-chatbot]').forEach(initChatbot);
});
