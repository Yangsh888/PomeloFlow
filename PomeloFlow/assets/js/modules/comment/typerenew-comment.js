import { EventScope } from '../../core/runtime.js';

function createTypeRenewCommentBridge() {
  return {
    _formId: 'pf-comment-form',
    _anchorId: 'pf-comment-form-anchor',
    _activeReplyTrigger: null,
    _activeReplyItem: null,

    _getRespond() {
      return document.getElementById('pf-respond');
    },

    _ensureAnchor(respond) {
      if (!respond) return null;

      let anchor = document.getElementById(this._anchorId);
      if (!anchor) {
        anchor = document.createElement('span');
        anchor.id = this._anchorId;
        anchor.hidden = true;
        respond.insertAdjacentElement('afterend', anchor);
      }

      return anchor;
    },

    reply(id, coid, trigger = null) {
      const form = document.getElementById(this._formId);
      if (!form) return;
      const respond = this._getRespond();
      const anchor = this._ensureAnchor(respond);
      if (!respond || !anchor) return;
      this._activeReplyTrigger = trigger instanceof HTMLElement ? trigger : null;
      if (this._activeReplyItem instanceof HTMLElement) {
        this._activeReplyItem.classList.remove('is-reply-target');
      }

      const parentItem = document.getElementById('li-comment-' + id)
                      || document.getElementById('li-' + id);
      if (parentItem) {
        parentItem.appendChild(respond);
        if (parentItem instanceof HTMLElement) {
          parentItem.classList.add('is-reply-target');
          this._activeReplyItem = parentItem;
        }
      }

      let parentInput = form.querySelector('input[name="parent"]');
      if (!parentInput) {
        parentInput = document.createElement('input');
        parentInput.type = 'hidden';
        parentInput.name = 'parent';
        form.appendChild(parentInput);
      }
      parentInput.value = String(coid);

      const cancelEl = document.getElementById('pf-cancel-reply');
      if (cancelEl) cancelEl.hidden = false;

      const textarea = form.querySelector('textarea');
      if (textarea) textarea.focus();
    },

    cancelReply() {
      const form = document.getElementById(this._formId);
      if (!form) return;
      const respond = this._getRespond();
      const anchor = document.getElementById(this._anchorId);
      if (respond && anchor && anchor.parentNode) {
        anchor.parentNode.insertBefore(respond, anchor);
      }

      const parentInput = form.querySelector('input[name="parent"]');
      if (parentInput) parentInput.value = '0';

      const cancelEl = document.getElementById('pf-cancel-reply');
      if (cancelEl) cancelEl.hidden = true;
      if (this._activeReplyItem instanceof HTMLElement) {
        this._activeReplyItem.classList.remove('is-reply-target');
      }
      if (this._activeReplyTrigger instanceof HTMLElement) {
        this._activeReplyTrigger.focus();
      }
      this._activeReplyItem = null;
      this._activeReplyTrigger = null;
    }
  };
}

function initCommentActions(scope, bridge) {
  const form = document.getElementById('pf-comment-form');
  const submitBtn = document.getElementById('pf-comment-submit');
  const statusEl = document.getElementById('pf-comment-status');
  const replyingEl = document.getElementById('pf-comment-replying');
  const cancelWrap = document.getElementById('pf-cancel-reply');
  const replyButtons = () => Array.from(document.querySelectorAll('.pf-comment-reply-btn'));
  const setStatus = (text, isError = false) => {
    if (!statusEl) return;
    statusEl.textContent = text;
    statusEl.classList.toggle('is-error', isError);
  };

  if (form) {
    scope.on(form, 'submit', () => {
      form.setAttribute('aria-busy', 'true');
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = '提交中...';
      }
      setStatus('评论正在提交，请稍候。');
    });

    scope.on(form, 'invalid', event => {
      const field = event.target;
      if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement)) return;
      setStatus(`请先补充${field.getAttribute('aria-label') || field.name || '必填项'}。`, true);
    }, true);

    scope.on(form, 'input', () => {
      form.removeAttribute('aria-busy');
      setStatus('');
    });
  }

  scope.on(document, 'click', (event) => {
    if (!(event.target instanceof Element)) return;

    const replyBtn = event.target.closest('.pf-comment-reply-btn');
    if (replyBtn) {
      const commentId = replyBtn.getAttribute('data-comment-id');
      const coid = replyBtn.getAttribute('data-coid');
      const author = (replyBtn.getAttribute('aria-label') || '').replace(/^回复\s*/, '');
      if (commentId && coid) {
        event.preventDefault();
        bridge.reply(commentId, Number(coid), replyBtn);
        if (replyingEl && author) {
          replyingEl.textContent = `正在回复：${author}`;
        }
        if (author) {
          setStatus(`已进入回复模式，当前回复 ${author}。`);
        }
      }
      return;
    }

    const cancelBtn = event.target.closest('#pf-comment-cancel-btn');
    if (cancelBtn) {
      event.preventDefault();
      bridge.cancelReply();
      if (replyingEl) replyingEl.textContent = '';
      setStatus('已取消回复。');
    }
  });

  scope.on(document, 'keydown', (event) => {
    const active = document.activeElement;
    const buttons = replyButtons();

    if (event.key === 'Escape' && !cancelWrap?.hidden) {
      event.preventDefault();
      bridge.cancelReply();
      if (replyingEl) replyingEl.textContent = '';
      setStatus('已取消回复。');
      return;
    }

    if (!(active instanceof HTMLElement) || !active.classList.contains('pf-comment-reply-btn')) {
      return;
    }

    const index = buttons.indexOf(active);
    if (index === -1) return;

    let nextIndex = null;
    if (event.key === 'ArrowDown' || event.key === 'ArrowRight') nextIndex = Math.min(index + 1, buttons.length - 1);
    if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') nextIndex = Math.max(index - 1, 0);
    if (event.key === 'Home') nextIndex = 0;
    if (event.key === 'End') nextIndex = buttons.length - 1;

    if (nextIndex !== null && buttons[nextIndex]) {
      event.preventDefault();
      buttons[nextIndex].focus();
    }
  });
}

export function commentModule() {
  return {
    name: 'comment',
    guard: () => !!document.getElementById('pf-comment-form'),
    init: () => {
      const scope = new EventScope();
      const bridge = createTypeRenewCommentBridge();
      initCommentActions(scope, bridge);
    }
  };
}
