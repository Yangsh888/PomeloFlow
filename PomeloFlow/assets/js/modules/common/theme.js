import { $, setThemeColor } from '../../core/utils.js';
import { EventScope } from '../../core/runtime.js';

class ThemeManager {
  constructor(ctx, scope) {
    this.schema = ctx.config.colorSchema || 'auto';
    this.canToggle = this.schema === 'auto';
    this.storageKey = 'pf-theme';
    this.themeColors = {
      light: '#fffaf5',
      dark: '#161b22'
    };
    this.scope = scope;
    this._apply();
  }

  _readStoredTheme() {
    try {
      return localStorage.getItem(this.storageKey);
    } catch (_) {
      return null;
    }
  }

  _writeStoredTheme(theme) {
    try {
      localStorage.setItem(this.storageKey, theme);
    } catch (_) {}
  }

  _currentTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    if (current === 'dark' || current === 'light') {
      return current;
    }
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  _syncThemeColor() {
    setThemeColor(this.themeColors[this._currentTheme()] || this.themeColors.light);
  }

  _apply() {
    const html = document.documentElement;
    if (this.schema === 'auto') {
      const saved = this._readStoredTheme();
      if (saved === 'dark') {
        html.setAttribute('data-theme', 'dark');
      } else if (saved === 'light') {
        html.setAttribute('data-theme', 'light');
      } else {
        html.setAttribute('data-theme', window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      }
    } else {
      html.setAttribute('data-theme', this.schema);
    }
    this._syncThemeColor();
  }

  toggle() {
    if (!this.canToggle) return;
    const html = document.documentElement;
    const isDark = this._currentTheme() === 'dark';
    const next = isDark ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    this._writeStoredTheme(next);
    this._syncThemeColor();
    this._updateBtn();
  }

  _updateBtn() {
    const btn = $('#pf-theme-btn');
    if (!btn) return;
    const isDark = this._currentTheme() === 'dark';
    btn.setAttribute('aria-label', isDark ? '切换浅色模式' : '切换深色模式');
    btn.title = isDark ? '切换浅色模式' : '切换深色模式';
    const moonIcon = btn.querySelector('.pf-icon-moon');
    const sunIcon  = btn.querySelector('.pf-icon-sun');
    if (moonIcon) moonIcon.style.display = isDark ? 'none' : '';
    if (sunIcon)  sunIcon.style.display  = isDark ? '' : 'none';
  }

  init() {
    this._updateBtn();
    const btn = $('#pf-theme-btn');
    if (btn && this.canToggle) {
      this.scope.on(btn, 'click', () => this.toggle());
    }
    if (this.canToggle) {
      this.scope.on(window.matchMedia('(prefers-color-scheme: dark)'), 'change', () => {
        if (!this._readStoredTheme()) {
          document.documentElement.setAttribute(
            'data-theme',
            window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
          );
          this._syncThemeColor();
          this._updateBtn();
        }
      });
    }
  }
}

export function themeModule() {
  return {
    name: 'theme',
    init: (ctx) => {
      const scope = new EventScope();
      const theme = new ThemeManager(ctx, scope);
      theme.init();
    }
  };
}
