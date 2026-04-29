import { $, bindDismissibleLayer, encodePathSegment, focusFirstElement, lockBodyScroll, notifyLayerOpen, rememberActiveElement, restoreActiveElement, setBodyState, setLayerState, unlockBodyScroll } from '../../core/utils.js';
import { EventScope } from '../../core/runtime.js';

class SearchOverlay {
  constructor(config, scope) {
    this.overlay  = $('#pf-search-overlay');
    this.input    = $('#pf-search-input');
    this.openBtn  = $('#pf-search-btn');
    this.closeBtn = $('#pf-search-close');
    this.statusEl = $('#pf-search-status');
    this.form     = this.overlay ? this.overlay.querySelector('form') : null;
    this._isOpen  = false;
    this._lastFocused = null;
    this.searchPattern = String(config?.searchPattern || '');
    this.scope = scope;
  }

  _isEditableTarget(target) {
    if (!(target instanceof HTMLElement)) return false;
    if (target.isContentEditable) return true;
    const tag = target.tagName;
    return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';
  }

  init() {
    if (!this.overlay) return;

    if (this.openBtn) {
      this.scope.on(this.openBtn, 'click', () => this.open());
    }
    if (this.closeBtn) {
      this.scope.on(this.closeBtn, 'click', () => this.close());
    }
    if (this.form) {
      this.scope.on(this.form, 'submit', (e) => this._handleSubmit(e));
    }
    if (this.input) {
      this.scope.on(this.input, 'input', () => this._clearStatus());
    }

    this.scope.on(document, 'keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        if (!this._isOpen && this._isEditableTarget(document.activeElement)) {
          return;
        }
        e.preventDefault();
        this._isOpen ? this.close() : this.open();
      }
    });

    bindDismissibleLayer(this.scope, {
      source: 'search',
      panel: this.overlay,
      trigger: this.openBtn,
      isOpen: () => this._isOpen,
      close: (restoreFocus = true) => this.close(restoreFocus)
    });
  }

  open() {
    if (!this.overlay) return;
    notifyLayerOpen('search');
    this._lastFocused = rememberActiveElement();
    setLayerState(this.overlay, true);
    this._isOpen = true;
    if (this.openBtn) this.openBtn.setAttribute('aria-expanded', 'true');
    setBodyState('pf-search-open', true);
    lockBodyScroll('search');
    this._clearStatus();
    
    setTimeout(() => {
      if (this.input) {
        this.input.focus();
      } else {
        focusFirstElement(this.overlay);
      }
    }, 80);
  }

  close(restoreFocus = true) {
    if (!this.overlay) return;
    setLayerState(this.overlay, false);
    this._isOpen = false;
    if (this.openBtn) this.openBtn.setAttribute('aria-expanded', 'false');
    setBodyState('pf-search-open', false);
    unlockBodyScroll('search');
    this._clearStatus();
    if (restoreFocus) restoreActiveElement(this._lastFocused);
  }

  _setStatus(text, isError = false) {
    if (!this.statusEl) return;
    this.statusEl.textContent = text;
    this.statusEl.classList.toggle('is-error', isError);
  }

  _clearStatus() {
    this._setStatus('', false);
  }

  _handleSubmit(event) {
    if (!this.form || !this.input) return;

    const keywords = this.input.value.trim();
    if (!keywords) {
      event.preventDefault();
      this._setStatus('请输入关键词后再搜索。', true);
      this.input.focus();
      return;
    }

    const encoded = encodePathSegment(keywords);
    const pattern = this.searchPattern;
    if (!pattern || !pattern.includes('__PF_KEYWORDS__')) {
      return;
    }

    event.preventDefault();
    window.location.href = pattern.replace('__PF_KEYWORDS__', encoded);
  }
}

export function searchModule() {
  return {
    name: 'search',
    guard: () => !!$('#pf-search-overlay'),
    init: (ctx) => {
      const scope = new EventScope();
      const search = new SearchOverlay(ctx.config, scope);
      search.init();
    }
  };
}
