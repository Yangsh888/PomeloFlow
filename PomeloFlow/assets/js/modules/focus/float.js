import { $, bindDismissibleLayer, focusFirstElement, lockBodyScroll, notifyLayerOpen, rememberActiveElement, restoreActiveElement, setBodyState, setLayerState, unlockBodyScroll } from '../../core/utils.js';
import { EventScope } from '../../core/runtime.js';

class FloatPomodoro {
  constructor(scope) {
    this.btn        = $('#pf-pom-float-btn');
    this.panel      = $('#pf-pom-float-panel');
    this.sidebar    = $('.pf-home-sidebar');
    this.pomodoro   = $('#pf-pomodoro');
    this._isOpen    = false;
    this._inPanel   = false;
    this._lastFocused = null;
    this._postMode  = this.panel && this.panel.classList.contains('pf-pom-float-panel-post');
    this.scope      = scope;
  }

  init() {
    if (!this.btn) return;
    if (this.panel) {
      this.btn.setAttribute('aria-controls', 'pf-pom-float-panel');
      this.btn.setAttribute('aria-expanded', 'false');
      this.panel.setAttribute('aria-hidden', 'true');
      this.panel.setAttribute('inert', '');
    }

    this.scope.on(this.btn, 'click', () => this.toggle());
    bindDismissibleLayer(this.scope, {
      source: 'pomodoro',
      panel: this.panel,
      trigger: this.btn,
      isOpen: () => this._isOpen,
      close: (restoreFocus = true) => this.close(restoreFocus)
    });

    this.scope.on(window, 'resize', () => {
      if (!this._postMode && window.innerWidth >= 1024 && this._inPanel) {
        this._moveToSidebar();
        this.close(false);
      }
    });
  }

  toggle() {
    this._isOpen ? this.close() : this._open();
  }

  _open() {
    if (!this.panel) return;
    notifyLayerOpen('pomodoro');
    this._lastFocused = rememberActiveElement();
    if (!this._postMode && window.innerWidth < 1024 && this.pomodoro && !this._inPanel) {
      this.panel.appendChild(this.pomodoro);
      this._inPanel = true;
    }
    setLayerState(this.panel, true);
    setBodyState('pf-pom-float-open', true);
    if (window.innerWidth < 1024) {
      lockBodyScroll('pomodoro-float');
    }
    this.btn.setAttribute('aria-expanded', 'true');
    this._isOpen = true;
    focusFirstElement(this.panel);
  }

  close(restoreFocus = true) {
    if (!this.panel) return;
    setLayerState(this.panel, false);
    setBodyState('pf-pom-float-open', false);
    unlockBodyScroll('pomodoro-float');
    this.btn.setAttribute('aria-expanded', 'false');
    this._isOpen = false;
    if (restoreFocus) restoreActiveElement(this._lastFocused);
  }

  _moveToSidebar() {
    if (!this.pomodoro || !this.sidebar) return;
    this.sidebar.appendChild(this.pomodoro);
    this._inPanel = false;
  }
}

export function floatPomodoroModule() {
  return {
    name: 'float-pomodoro',
    guard: () => !!$('#pf-pom-float-panel'),
    init: () => {
      const scope = new EventScope();
      const floatPom = new FloatPomodoro(scope);
      floatPom.init();
    }
  };
}
