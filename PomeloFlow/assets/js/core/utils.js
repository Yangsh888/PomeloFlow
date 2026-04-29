/**
 * 核心工具函数
 */

export const $ = (sel, ctx = document) => ctx.querySelector(sel);
export const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

export const FOCUSABLE_SELECTOR = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled]):not([type="hidden"])',
  'textarea:not([disabled])',
  'select:not([disabled])',
  '[tabindex]:not([tabindex="-1"])'
].join(',');

export function clamp(val, min, max) {
  return Math.min(Math.max(val, min), max);
}

export function getFocusableElements(container) {
  if (!container) return [];
  return $$(FOCUSABLE_SELECTOR, container).filter(el => !el.hidden && el.offsetParent !== null);
}

export function rememberActiveElement() {
  return document.activeElement instanceof HTMLElement ? document.activeElement : null;
}

export function restoreActiveElement(target) {
  if (target && typeof target.focus === 'function') {
    target.focus();
  }
}

export function focusFirstElement(container) {
  const first = getFocusableElements(container)[0];
  if (first) {
    first.focus();
  }
}

export function trapFocus(event, container) {
  const focusables = getFocusableElements(container);
  if (!focusables.length) return;

  const first = focusables[0];
  const last = focusables[focusables.length - 1];

  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault();
    last.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault();
    first.focus();
  }
}

export async function fetchWithTimeout(url, options = {}, timeout = 5000) {
  const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
  const timer = controller ? window.setTimeout(() => controller.abort(), timeout) : null;

  try {
    const nextOptions = { ...options };
    if (controller) {
      nextOptions.signal = controller.signal;
    }
    return await fetch(url, nextOptions);
  } finally {
    if (timer) {
      window.clearTimeout(timer);
    }
  }
}

export function encodePathSegment(value) {
  return encodeURIComponent(String(value))
    .replace(/[!'()*]/g, ch => '%' + ch.charCodeAt(0).toString(16).toUpperCase());
}

export function emitUiEvent(name, detail = {}) {
  document.dispatchEvent(new CustomEvent(name, { detail }));
}

export function onUiEvent(scope, name, handler) {
  scope.on(document, name, event => {
    handler(event?.detail || {}, event);
  });
}

export function notifyLayerOpen(source) {
  emitUiEvent('pf:layer-open', { source });
}

export function onOtherLayerOpen(scope, source, onOpen) {
  onUiEvent(scope, 'pf:layer-open', detail => {
    if (!detail?.source || detail.source === source) return;
    onOpen(detail.source);
  });
}

export function setBodyState(state, active) {
  if (!document.body || !state) return;
  document.body.classList.toggle(state, !!active);
}

export function setLayerState(layer, active, openClass = 'open') {
  if (!layer) return;
  if (openClass) {
    layer.classList.toggle(openClass, !!active);
  }
  layer.setAttribute('aria-hidden', active ? 'false' : 'true');
  if (active) {
    layer.removeAttribute('inert');
  } else {
    layer.setAttribute('inert', '');
  }
}

export function bindDismissibleLayer(scope, options) {
  const {
    source,
    panel,
    trigger,
    isOpen,
    close,
    trapTarget = panel,
    preventEscape = false
  } = options || {};

  if (!scope || !panel || typeof isOpen !== 'function' || typeof close !== 'function') {
    return;
  }

  scope.on(document, 'click', event => {
    if (!isOpen()) return;
    if (!(event.target instanceof Node)) return;
    if (!panel.contains(event.target) && !(trigger && trigger.contains(event.target))) {
      close(false);
    }
  });

  scope.on(document, 'keydown', event => {
    if (!isOpen()) return;
    if (event.key === 'Escape') {
      if (preventEscape) {
        event.preventDefault();
      }
      close();
      return;
    }
    if (event.key === 'Tab') {
      trapFocus(event, trapTarget || panel);
    }
  });

  if (source) {
    onOtherLayerOpen(scope, source, () => close(false));
  }
}

const bodyScrollLocks = new Set();
let previousBodyOverflow = null;
let previousBodyPaddingRight = null;

export function lockBodyScroll(owner = 'default') {
  if (!document.body) return;
  if (!bodyScrollLocks.size) {
    previousBodyOverflow = document.body.style.overflow;
    previousBodyPaddingRight = document.body.style.paddingRight;
    const scrollbarWidth = Math.max(0, window.innerWidth - document.documentElement.clientWidth);
    if (scrollbarWidth > 0) {
      const currentPaddingRight = parseFloat(window.getComputedStyle(document.body).paddingRight) || 0;
      document.body.style.paddingRight = `${currentPaddingRight + scrollbarWidth}px`;
    }
    document.body.style.overflow = 'hidden';
  }
  bodyScrollLocks.add(owner);
}

export function unlockBodyScroll(owner = 'default') {
  if (!document.body) return;
  bodyScrollLocks.delete(owner);
  if (!bodyScrollLocks.size) {
    document.body.style.overflow = previousBodyOverflow ?? '';
    document.body.style.paddingRight = previousBodyPaddingRight ?? '';
    previousBodyOverflow = null;
    previousBodyPaddingRight = null;
  }
}

export function setThemeColor(color) {
  if (!color) return;
  const meta = document.querySelector('meta[name="theme-color"]');
  if (meta) {
    meta.setAttribute('content', color);
  }
}
