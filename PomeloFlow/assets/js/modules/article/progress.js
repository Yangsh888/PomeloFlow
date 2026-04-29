import { $ } from '../../core/utils.js';
import { EventScope } from '../../core/runtime.js';

class ReadingProgress {
  constructor(scope) {
    this.bar     = $('#pf-reading-bar');
    this.ticking = false;
    this.scope   = scope;
  }

  init() {
    if (!this.bar) return;
    this.scope.on(window, 'scroll', () => {
      if (!this.ticking) {
        requestAnimationFrame(() => {
          this._update();
          this.ticking = false;
        });
        this.ticking = true;
      }
    }, { passive: true });
    this._update();
  }

  _update() {
    const docH   = document.documentElement.scrollHeight - window.innerHeight;
    const scrollY = window.scrollY;
    const ratio  = docH > 0 ? scrollY / docH : 0;
    this.bar.style.transform = `scaleX(${ratio})`;
  }
}

export function progressModule() {
  return {
    name: 'reading-progress',
    guard: () => !!$('#pf-reading-bar'),
    init: () => {
      const scope = new EventScope();
      const progress = new ReadingProgress(scope);
      progress.init();
    }
  };
}
