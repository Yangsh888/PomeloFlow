import { fetchWithTimeout } from '../../core/utils.js';

export class HitokotoTips {
  constructor(textEl, mode = 'local', defaults = []) {
    this.el      = textEl;
    this.timer   = null;
    this.showTimer = null;
    this.interval = 18000;
    this.mode = mode === 'api' ? 'api' : 'local';
    this.sessionId = 0;
    this.initialText = (textEl?.textContent || '').trim();
    this._defaults = defaults.length ? defaults : [this.initialText || '保持专注，稳步推进当前任务。'];
  }

  async fetch() {
    try {
      const res = await fetchWithTimeout('https://v1.hitokoto.cn/?c=a&c=b&c=c&c=d&encode=json', {}, 4000);
      if (!res.ok) throw new Error('hitokoto http ' + res.status);
      const data = await res.json();
      return (data.hitokoto || '').trim();
    } catch (_) {
      return null;
    }
  }

  _random() {
    return this._defaults[Math.floor(Math.random() * this._defaults.length)];
  }

  _show(text) {
    if (!this.el || !text) return;
    clearTimeout(this.showTimer);
    this.el.classList.add('fading');
    this.showTimer = setTimeout(() => {
      this.el.textContent = text;
      this.el.classList.remove('fading');
    }, 300);
  }

  async _nextText(sessionId, useRemote = this.mode === 'api') {
    const fallback = this._random();
    if (!useRemote) {
      return fallback;
    }
    const remote = await this.fetch();
    if (sessionId !== this.sessionId) return null;
    return remote || fallback;
  }

  async start() {
    this.stop();
    const sessionId = ++this.sessionId;
    let text = this.initialText || this._random();

    if (this.mode === 'api') {
      text = (await this._nextText(sessionId)) || text;
      if (sessionId !== this.sessionId) return;
      this._show(text);
    } else if (!this.initialText) {
      if (this.el && text) {
        clearTimeout(this.showTimer);
        this.el.textContent = text;
        this.el.classList.remove('fading');
      }
    }

    this.timer = setInterval(async () => {
      const next = await this._nextText(sessionId, true);
      if (sessionId !== this.sessionId || !next) return;
      this._show(next);
    }, this.interval);
  }

  stop() {
    this.sessionId += 1;
    clearInterval(this.timer);
    clearTimeout(this.showTimer);
    this.timer = null;
    this.showTimer = null;
  }

  setPhase(phase) {
    const breakTips = [
      '站起来走动走动吧，活动一下筋骨。',
      '喝杯水，补充一下水分。',
      '闭上眼睛休息20秒，让眼睛放松一下。',
      '做几个深呼吸，放松一下肩膀。',
      '休息片刻，回来会更有效率。',
      '离开屏幕，看看窗外的风景。',
    ];
    if (phase !== 'focus') {
      this.stop();
      const tip = breakTips[Math.floor(Math.random() * breakTips.length)];
      this._show(tip);
    } else {
      this.start();
    }
  }
}
