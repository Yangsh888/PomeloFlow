import { getContentContext, getContentRoot } from './content.js';
import { EventScope } from '../../core/runtime.js';
import { focusFirstElement, lockBodyScroll, notifyLayerOpen, rememberActiveElement, restoreActiveElement, setLayerState, trapFocus, unlockBodyScroll } from '../../core/utils.js';

class Lightbox {
  constructor(scope) {
    this.overlay  = null;
    this.imgEl    = null;
    this.captionEl = null;
    this.closeBtn = null;
    this._isOpen = false;
    this._lastFocused = null;
    this.scope = scope;
  }

  init() {
    this.overlay = document.createElement('div');
    this.overlay.className = 'pf-lightbox';
    this.overlay.setAttribute('role', 'dialog');
    this.overlay.setAttribute('aria-modal', 'true');
    this.overlay.setAttribute('aria-labelledby', 'pf-lightbox-title');
    this.overlay.setAttribute('aria-hidden', 'true');
    this.overlay.setAttribute('inert', '');

    const titleEl = document.createElement('h2');
    titleEl.className = 'sr-only';
    titleEl.id = 'pf-lightbox-title';
    titleEl.textContent = '图片预览';

    this.imgEl = document.createElement('img');
    this.imgEl.className = 'pf-lightbox-img';
    this.imgEl.alt = '';

    this.captionEl = document.createElement('p');
    this.captionEl.className = 'pf-lightbox-caption';
    this.captionEl.id = 'pf-lightbox-caption';
    this.captionEl.hidden = true;

    const hintEl = document.createElement('p');
    hintEl.className = 'pf-lightbox-hint';
    hintEl.id = 'pf-lightbox-hint';
    hintEl.textContent = '按 Esc 关闭，点击遮罩返回正文。';

    this.closeBtn = document.createElement('button');
    this.closeBtn.className = 'pf-lightbox-close';
    this.closeBtn.type = 'button';
    this.closeBtn.setAttribute('aria-label', '关闭');
    const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    icon.setAttribute('viewBox', '0 0 24 24');
    icon.setAttribute('aria-hidden', 'true');
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', 'M18 6L6 18M6 6l12 12');
    path.setAttribute('stroke', 'currentColor');
    path.setAttribute('stroke-width', '2');
    path.setAttribute('stroke-linecap', 'round');
    icon.appendChild(path);
    this.closeBtn.appendChild(icon);

    this.overlay.appendChild(titleEl);
    this.overlay.appendChild(this.imgEl);
    this.overlay.appendChild(this.captionEl);
    this.overlay.appendChild(hintEl);
    this.overlay.appendChild(this.closeBtn);
    document.body.appendChild(this.overlay);

    this.scope.on(this.closeBtn, 'click', () => this.close());
    this.scope.on(this.overlay, 'click', (e) => {
      if (e.target === this.overlay) this.close();
    });
    this.scope.on(document, 'keydown', (e) => {
      if (e.key === 'Escape' && this._isOpen) this.close();
      if (e.key === 'Tab' && this._isOpen) trapFocus(e, this.overlay);
    });

    const context = getContentContext();
    if (!context) return;
    context.images.forEach(img => {
      const isLinkedImage = !!(img.parentElement && img.parentElement.tagName === 'A');
      if (isLinkedImage) {
        img.style.cursor = 'pointer';
        return;
      }
      this._enhanceImage(img);
      img.style.cursor = 'zoom-in';
      img.tabIndex = 0;
      img.setAttribute('role', 'button');
      img.setAttribute('aria-haspopup', 'dialog');
      img.setAttribute('aria-label', `查看大图：${img.alt || '文章配图'}`);
      this.scope.on(img, 'click', () => this.open(img.src, img.alt));
      this.scope.on(img, 'keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          this.open(img.src, img.alt);
        }
      });
    });
  }

  _enhanceImage(img) {
    if (!(img instanceof HTMLImageElement)) return;
    if (img.closest('figure')) return;
    const alt = (img.getAttribute('alt') || '').trim();
    if (!alt) return;
    const parent = img.parentElement;
    if (!parent) return;
    if (parent.tagName === 'P' && this._canPromoteParagraph(parent, img)) {
      const figure = document.createElement('figure');
      figure.className = 'pf-figure';
      parent.replaceWith(figure);
      figure.appendChild(img);

      const caption = document.createElement('figcaption');
      caption.className = 'pf-caption';
      caption.textContent = alt;
      figure.appendChild(caption);
    }
  }

  _canPromoteParagraph(paragraph, img) {
    if (!(paragraph instanceof HTMLParagraphElement)) return false;
    const meaningfulNodes = Array.from(paragraph.childNodes).filter(node => {
      if (node === img) return true;
      if (node.nodeType === Node.TEXT_NODE) {
        return (node.textContent || '').trim() !== '';
      }
      if (!(node instanceof HTMLElement)) return false;
      return node !== img && node.tagName !== 'BR';
    });

    return meaningfulNodes.length === 1 && meaningfulNodes[0] === img;
  }

  open(src, alt) {
    if (!src) return;
    notifyLayerOpen('lightbox');
    this._lastFocused = rememberActiveElement();
    this.imgEl.src = src;
    this.imgEl.alt = alt || '';
    this.overlay.setAttribute('aria-describedby', alt ? 'pf-lightbox-caption pf-lightbox-hint' : 'pf-lightbox-hint');
    if (this.captionEl) {
      this.captionEl.textContent = alt || '';
      this.captionEl.hidden = !alt;
    }
    setLayerState(this.overlay, true);
    this._isOpen = true;
    lockBodyScroll('lightbox');
    focusFirstElement(this.overlay);
  }

  close() {
    if (!this.overlay || !this._isOpen) return;
    setLayerState(this.overlay, false);
    this.overlay.removeAttribute('aria-describedby');
    this._isOpen = false;
    unlockBodyScroll('lightbox');
    setTimeout(() => { if (this.imgEl) this.imgEl.src = ''; }, 300);
    if (this.captionEl) {
      this.captionEl.textContent = '';
      this.captionEl.hidden = true;
    }
    restoreActiveElement(this._lastFocused);
  }
}

export function lightboxModule() {
  return {
    name: 'lightbox',
    guard: () => !!getContentRoot(),
    init: () => {
      const scope = new EventScope();
      const lightbox = new Lightbox(scope);
      lightbox.init();
    }
  };
}
