import { EventScope } from '../../core/runtime.js';
import { getContentContext, getContentRoot } from './content.js';

class CodeCopy {
  constructor(scope) {
    this.scope = scope;
  }

  async _copyText(text) {
    if (navigator.clipboard?.writeText && window.isSecureContext) {
      await navigator.clipboard.writeText(text);
      return;
    }

    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.top = '0';
    textarea.style.left = '0';
    textarea.style.opacity = '0';
    textarea.style.pointerEvents = 'none';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    const copied = document.execCommand && document.execCommand('copy');

    if (window.getSelection) {
      window.getSelection()?.removeAllRanges();
    }

    document.body.removeChild(textarea);

    if (!copied) {
      throw new Error('Copy command is unavailable');
    }
  }

  init() {
    const context = getContentContext();
    if (!context) return;
    context.preBlocks.forEach(pre => {
      if (pre.querySelector('.pf-code-copy')) return;
      if (!pre.style.position) {
        pre.style.position = 'relative';
      }
      const code = pre.querySelector('code');
      if (code) {
        const className = code.className || '';
        const match = className.match(/(?:^|\s)language-([a-z0-9#+._-]+)/i);
        if (match && !pre.querySelector('.pf-code-lang')) {
          const lang = document.createElement('span');
          lang.className = 'pf-code-lang';
          lang.setAttribute('data-pf-code-lang', '1');
          lang.textContent = match[1].toUpperCase();
          pre.appendChild(lang);
        }
      }
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'pf-code-copy';
      btn.textContent = '复制';
      btn.setAttribute('aria-label', '复制代码');
      btn.setAttribute('aria-live', 'polite');
      this.scope.on(btn, 'click', () => {
        const code = pre.querySelector('code');
        const text = code ? code.textContent : pre.textContent;
        this._copyText(text || '').then(() => {
          btn.textContent = '已复制';
          setTimeout(() => { btn.textContent = '复制'; }, 2000);
        }).catch(() => {
          btn.textContent = '失败';
          setTimeout(() => { btn.textContent = '复制'; }, 2000);
        });
      });
      pre.appendChild(btn);
    });
  }
}

export function codeCopyModule() {
  return {
    name: 'code-copy',
    guard: () => !!getContentRoot(),
    init: () => {
      const scope = new EventScope();
      const codeCopy = new CodeCopy(scope);
      codeCopy.init();
    }
  };
}
