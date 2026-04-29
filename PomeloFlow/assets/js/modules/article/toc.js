import { $, bindDismissibleLayer, focusFirstElement, lockBodyScroll, notifyLayerOpen, rememberActiveElement, restoreActiveElement, setBodyState, setLayerState, unlockBodyScroll } from '../../core/utils.js';
import { EventScope } from '../../core/runtime.js';
import { getContentContext } from './content.js';

const TOC_MOBILE_MAX_WIDTH = 767;

class TableOfContents {
  constructor(scope) {
    this.toc      = $('#pf-toc');
    this.toggleBtn = $('#pf-toc-toggle');
    this.list     = $('#pf-toc-list');
    this.context  = getContentContext();
    this.content  = this.context ? this.context.root : null;
    this.items    = [];
    this.ticking  = false;
    this.isMobileOpen = false;
    this._lastFocused = null;
    this.scope = scope;
  }

  init() {
    if (!this.toc || !this.content) return;
    const headings = this.context ? this.context.headings : [];

    if (!headings.length) {
      this.isMobileOpen = false;
      this.toc.classList.remove('mobile-open');
      if (!this.toc.classList.contains('force-show')) {
        this.toc.setAttribute('aria-hidden', 'true');
        this.toc.setAttribute('inert', '');
        if (this.toggleBtn) {
          this.toggleBtn.hidden = true;
          this.toggleBtn.setAttribute('aria-expanded', 'false');
          this.toggleBtn.setAttribute('aria-label', '打开文章目录');
        }
        return;
      }
      if (this.list) {
        const empty = document.createElement('div');
        empty.className = 'pf-toc-empty';
        empty.textContent = '正文里还没有可折叠的标题';
        this.list.replaceChildren(empty);
      }
      this.toc.classList.add('visible');
      if (this.toggleBtn) this.toggleBtn.hidden = true;
      return;
    }

    headings.forEach((h, i) => {
      if (!h.id) h.id = 'pf-h-' + i;
      const level = parseInt(h.tagName.slice(1), 10);
      const a = document.createElement('a');
      a.href = '#' + h.id;
      a.textContent = h.textContent || '';
      
      this.scope.on(a, 'click', () => {
        if (this.isMobileOpen) {
          this._closeMobile(false);
        }
      });

      const li = document.createElement('div');
      li.className = 'pf-toc-item h' + level;
      li.appendChild(a);
      if (this.list) this.list.appendChild(li);
      this.items.push({ el: h, link: a });
    });

    this.toc.classList.add('visible');
    this._bindResponsiveToggle();
    this._syncMode();

    this.scope.on(window, 'scroll', () => {
      if (!this.ticking) {
        requestAnimationFrame(() => {
          this._highlight();
          this.ticking = false;
        });
        this.ticking = true;
      }
    }, { passive: true });

    this.scope.on(window, 'resize', () => this._syncMode());
    this._highlight();
  }

  _bindResponsiveToggle() {
    if (!this.toggleBtn) return;
    this.scope.on(this.toggleBtn, 'click', () => {
      this.isMobileOpen ? this._closeMobile() : this._openMobile();
    });
    bindDismissibleLayer(this.scope, {
      source: 'toc',
      panel: this.toc,
      trigger: this.toggleBtn,
      isOpen: () => this.isMobileOpen,
      close: (restoreFocus = true) => this._closeMobile(restoreFocus)
    });
  }

  _syncMode() {
    const isMobile = window.innerWidth <= TOC_MOBILE_MAX_WIDTH && !!this.toggleBtn;
    if (this.toggleBtn) {
      this.toggleBtn.hidden = !isMobile;
      this.toggleBtn.setAttribute('aria-expanded', this.isMobileOpen ? 'true' : 'false');
      this.toggleBtn.setAttribute('aria-label', this.isMobileOpen ? '关闭文章目录' : '打开文章目录');
    }

    if (isMobile) {
      this.toc.setAttribute('role', 'dialog');
      this.toc.setAttribute('aria-modal', 'true');
      if (!this.isMobileOpen) {
        setLayerState(this.toc, false, 'mobile-open');
      }
      return;
    }

    this.isMobileOpen = false;
    setBodyState('pf-toc-open', false);
    unlockBodyScroll('toc');
    setLayerState(this.toc, true, null);
    this.toc.classList.remove('mobile-open');
    this.toc.removeAttribute('role');
    this.toc.removeAttribute('aria-modal');
    if (this.toggleBtn) {
      this.toggleBtn.setAttribute('aria-expanded', 'false');
      this.toggleBtn.setAttribute('aria-label', '打开文章目录');
    }
  }

  _openMobile() {
    if (!this.toggleBtn || window.innerWidth > TOC_MOBILE_MAX_WIDTH) return;
    notifyLayerOpen('toc');
    this._lastFocused = rememberActiveElement();
    this.isMobileOpen = true;
    setBodyState('pf-toc-open', true);
    lockBodyScroll('toc');
    setLayerState(this.toc, true, 'mobile-open');
    this.toggleBtn.setAttribute('aria-expanded', 'true');
    this.toggleBtn.setAttribute('aria-label', '关闭文章目录');
    focusFirstElement(this.toc);
  }

  _closeMobile(restoreFocus = true) {
    if (!this.toggleBtn) return;
    this.isMobileOpen = false;
    setBodyState('pf-toc-open', false);
    unlockBodyScroll('toc');
    setLayerState(this.toc, false, 'mobile-open');
    this.toggleBtn.setAttribute('aria-expanded', 'false');
    this.toggleBtn.setAttribute('aria-label', '打开文章目录');
    if (restoreFocus) restoreActiveElement(this._lastFocused);
  }

  _highlight() {
    const navH   = 60 + 24;
    const scrollY = window.scrollY;
    let active   = -1;

    this.items.forEach((item, i) => {
      const top = item.el.getBoundingClientRect().top + scrollY - navH;
      if (scrollY >= top - 4) active = i;
    });

    this.items.forEach((item, i) => {
      item.link.classList.toggle('active', i === active);
      if (i === active) {
        item.link.setAttribute('aria-current', 'location');
      } else {
        item.link.removeAttribute('aria-current');
      }
    });
  }
}

export function tocModule() {
  return {
    name: 'toc',
    guard: () => !!$('#pf-toc') && !!$('#pf-post-content'),
    init: () => {
      const scope = new EventScope();
      const toc = new TableOfContents(scope);
      toc.init();
    }
  };
}
