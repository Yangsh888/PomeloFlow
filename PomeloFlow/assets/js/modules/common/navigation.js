import { $, $$, bindDismissibleLayer, focusFirstElement, lockBodyScroll, notifyLayerOpen, rememberActiveElement, restoreActiveElement, setBodyState, setLayerState, unlockBodyScroll } from '../../core/utils.js';
import { EventScope } from '../../core/runtime.js';

class Navigation {
  constructor(scope) {
    this.nav       = $('#pf-nav');
    this.hamburger = $('#pf-hamburger');
    this.mobileMenu = $('#pf-mobile-menu');
    this.backTopBtn = $('#pf-backtop');
    this.ticking   = false;
    this._lastFocused = null;
    this.scope = scope;
  }

  init() {
    if (!this.nav) return;

    this.scope.on(window, 'scroll', () => {
      if (!this.ticking) {
        requestAnimationFrame(() => {
          this._onScroll();
          this.ticking = false;
        });
        this.ticking = true;
      }
    }, { passive: true });

    if (this.hamburger && this.mobileMenu) {
      this.mobileMenu.setAttribute('aria-hidden', 'true');
      this.mobileMenu.setAttribute('inert', '');
      this.scope.on(this.hamburger, 'click', () => this._toggleMobile());

      bindDismissibleLayer(this.scope, {
        source: 'nav-menu',
        panel: this.mobileMenu,
        trigger: this.hamburger,
        isOpen: () => this.mobileMenu.classList.contains('open'),
        close: (restoreFocus = true) => this._closeMobile(restoreFocus)
      });

      this.scope.on(window, 'resize', () => {
        if (window.innerWidth >= 1024 && this.mobileMenu.classList.contains('open')) {
          this._closeMobile(false);
        }
      });
    }

    if (this.backTopBtn) {
      this.scope.on(this.backTopBtn, 'click', () => {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
      });
    }

    this._onScroll();
    this._markActive();
  }

  _onScroll() {
    const y = window.scrollY;
    if (y > 60) {
      this.nav.classList.add('nav-scrolled');
    } else {
      this.nav.classList.remove('nav-scrolled');
    }
    if (this.backTopBtn) {
      const visible = y > 420;
      this.backTopBtn.classList.toggle('is-visible', visible);
      this.backTopBtn.setAttribute('aria-hidden', visible ? 'false' : 'true');
      this.backTopBtn.tabIndex = visible ? 0 : -1;
    }
  }

  _toggleMobile() {
    const open = this.mobileMenu.classList.contains('open');
    if (open) {
      this._closeMobile();
    } else {
      notifyLayerOpen('nav-menu');
      this._lastFocused = rememberActiveElement();
      setLayerState(this.mobileMenu, true);
      this.hamburger.classList.add('open');
      this.hamburger.setAttribute('aria-expanded', 'true');
      setBodyState('pf-nav-menu-open', true);
      lockBodyScroll('nav-menu');
      focusFirstElement(this.mobileMenu);
    }
  }

  _closeMobile(restoreFocus = true) {
    setLayerState(this.mobileMenu, false);
    this.hamburger.classList.remove('open');
    this.hamburger.setAttribute('aria-expanded', 'false');
    setBodyState('pf-nav-menu-open', false);
    unlockBodyScroll('nav-menu');
    if (restoreFocus) restoreActiveElement(this._lastFocused);
  }

  _markActive() {
    const normalizePath = (value) => {
      if (!value) return '/';
      const next = value.replace(/\/+$/, '');
      return next === '' ? '/' : next;
    };
    const path = normalizePath(window.location.pathname);
    $$('.pf-nav-link, .pf-mobile-menu a').forEach(a => {
      try {
        const url = new URL(a.href, window.location.origin);
        if (normalizePath(url.pathname) === path) {
          a.setAttribute('aria-current', 'page');
        } else {
          a.removeAttribute('aria-current');
        }
      } catch (_) { }
    });
  }
}

export function navigationModule() {
  return {
    name: 'navigation',
    guard: () => !!$('#pf-nav'),
    init: () => {
      const scope = new EventScope();
      const nav = new Navigation(scope);
      nav.init();
    }
  };
}
