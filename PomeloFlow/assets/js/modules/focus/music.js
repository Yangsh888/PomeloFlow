import { $, bindDismissibleLayer, fetchWithTimeout, focusFirstElement, notifyLayerOpen, rememberActiveElement, restoreActiveElement, setLayerState } from '../../core/utils.js';
import { EventScope } from '../../core/runtime.js';

class MusicPlayer {
  constructor(tracks, neteaseApi, scope) {
    this.tracks     = tracks || [];
    this.neteaseApi = neteaseApi || '';
    this.current    = 0;
    this.audio      = new Audio();
    this.audio.preload = 'none';
    this.wrapper    = $('#pf-music');
    this.bar        = $('#pf-music-bar');
    this.panel      = $('#pf-music-panel');
    this.titleEl    = $('#pf-music-title');
    this.artistEl   = $('#pf-music-artist');
    this.statusEl   = $('#pf-music-status');
    this.playBtn    = $('#pf-music-play');
    this.prevBtn    = $('#pf-music-prev');
    this.nextBtn    = $('#pf-music-next');
    this.loadFailed = false;
    this._loadToken = 0;
    this.isExpanded = false;
    this._lastFocused = null;
    this.scope      = scope;
  }

  _setStatus(text, isError = false) {
    if (!this.statusEl) return;
    this.statusEl.textContent = text;
    this.statusEl.classList.toggle('is-error', isError);
  }

  _setControlsDisabled(disabled) {
    [this.playBtn, this.prevBtn, this.nextBtn].forEach(btn => {
      if (!btn) return;
      btn.disabled = disabled;
    });
  }

  _normalizePlayableUrl(url) {
    const nextUrl = String(url || '').trim();
    if (!nextUrl) return '';
    if (window.location.protocol === 'https:' && /^http:\/\//i.test(nextUrl)) {
      return 'https://' + nextUrl.slice(7);
    }
    return nextUrl;
  }

  async _resolveUrl(track) {
    const url = track.url || '';
    if (this.neteaseApi && /^\d+$/.test(url.trim())) {
      try {
        const api = this.neteaseApi.replace(/\/$/, '');
        const res = await fetchWithTimeout(`${api}/song/url?id=${url.trim()}`, {}, 5000);
        if (!res.ok) throw new Error('netease api error');
        const data = await res.json();
        const resolved = data?.data?.[0]?.url;
        if (resolved) return this._normalizePlayableUrl(resolved);
      } catch (_) { }
    }
    return this._normalizePlayableUrl(url);
  }

  async _load(index) {
    const track = this.tracks[index];
    if (!track) return false;
    const loadToken = ++this._loadToken;
    if (this.titleEl)  this.titleEl.textContent = track.name || '未知曲目';
    if (this.artistEl) this.artistEl.textContent = track.artist || '';
    this._setStatus('解析中…');
    this._setControlsDisabled(true);
    this.loadFailed = false;

    const url = await this._resolveUrl(track);
    if (loadToken !== this._loadToken || index !== this.current) {
      return false;
    }
    if (!url) {
      this.loadFailed = true;
      this.audio.removeAttribute('src');
      this.audio.load();
      this.pause();
      this._setControlsDisabled(false);
      this._setStatus('当前音频不可用，请切换下一首重试', true);
      return false;
    }

    this.audio.src = url;
    this.audio.load();
    this._setControlsDisabled(false);
    this._setStatus('待播放');
    return true;
  }

  play() {
    if (!this.tracks.length || this.loadFailed || !this.audio.src) {
      this._setStatus('当前音频不可用，请切换下一首重试', true);
      return;
    }
    this.audio.play().then(() => {
      this._setStatus('播放中');
    }).catch(() => {
      this.pause();
      this._setStatus('浏览器阻止了播放，请再点一次', true);
    });
    if (this.wrapper) this.wrapper.classList.add('playing');
    if (this.playBtn) {
      const playIcon = this.playBtn.querySelector('.pf-icon-play');
      const pauseIcon = this.playBtn.querySelector('.pf-icon-pause');
      if (playIcon)  playIcon.style.display = 'none';
      if (pauseIcon) pauseIcon.style.display = '';
    }
  }

  pause() {
    this.audio.pause();
    if (this.wrapper) this.wrapper.classList.remove('playing');
    if (!this.loadFailed) this._setStatus('已暂停');
    if (this.playBtn) {
      const playIcon = this.playBtn.querySelector('.pf-icon-play');
      const pauseIcon = this.playBtn.querySelector('.pf-icon-pause');
      if (playIcon)  playIcon.style.display = '';
      if (pauseIcon) pauseIcon.style.display = 'none';
    }
  }

  togglePlay() {
    if (this.audio.paused) {
      this.play();
    } else {
      this.pause();
    }
  }

  async prev(shouldResume = !this.audio.paused && !this.loadFailed) {
    this.current = (this.current - 1 + this.tracks.length) % this.tracks.length;
    const loaded = await this._load(this.current);
    if (loaded && shouldResume && !this.loadFailed) this.play();
  }

  async next(shouldResume = !this.audio.paused && !this.loadFailed) {
    this.current = (this.current + 1) % this.tracks.length;
    const loaded = await this._load(this.current);
    if (loaded && shouldResume && !this.loadFailed) this.play();
  }

  _openPanel() {
    if (!this.wrapper || !this.panel || this.isExpanded) return;
    notifyLayerOpen('music');
    this._lastFocused = rememberActiveElement();
    this.wrapper.classList.add('expanded');
    this.isExpanded = true;
    if (this.bar) this.bar.setAttribute('aria-expanded', 'true');
    setLayerState(this.panel, true, null);
    focusFirstElement(this.panel);
  }

  _closePanel(restoreFocus = true) {
    if (!this.wrapper || !this.panel || !this.isExpanded) return;
    this.wrapper.classList.remove('expanded');
    this.isExpanded = false;
    if (this.bar) this.bar.setAttribute('aria-expanded', 'false');
    setLayerState(this.panel, false, null);
    if (restoreFocus) restoreActiveElement(this._lastFocused);
  }

  _togglePanel() {
    if (this.isExpanded) {
      this._closePanel();
    } else {
      this._openPanel();
    }
  }

  init() {
    if (!this.wrapper || !this.tracks.length) return;

    this._load(this.current);

    if (this.bar) {
      this.scope.on(this.bar, 'click', () => {
        this._togglePanel();
      });
    }

    if (this.playBtn) this.scope.on(this.playBtn, 'click', (e) => { e.stopPropagation(); this.togglePlay(); });
    if (this.prevBtn) this.scope.on(this.prevBtn, 'click', (e) => { e.stopPropagation(); this.prev(); });
    if (this.nextBtn) this.scope.on(this.nextBtn, 'click', (e) => { e.stopPropagation(); this.next(); });

    bindDismissibleLayer(this.scope, {
      source: 'music',
      panel: this.wrapper,
      trigger: this.bar,
      trapTarget: this.panel,
      isOpen: () => this.isExpanded,
      close: (restoreFocus = true) => this._closePanel(restoreFocus),
      preventEscape: true
    });

    this.scope.on(this.audio, 'ended', () => this.next(true));
    this.scope.on(this.audio, 'error', () => {
      this.loadFailed = true;
      this.pause();
      this._setStatus('音频加载失败，请切换下一首重试', true);
    });
  }
}

export function musicModule() {
  return {
    name: 'music',
    guard: (ctx) => {
      const cfg = ctx.config;
      return (cfg.musicEnable === true || cfg.musicEnable === 'true') && cfg.musicTracks && cfg.musicTracks.length > 0;
    },
    init: (ctx) => {
      const scope = new EventScope();
      const player = new MusicPlayer(ctx.config.musicTracks, ctx.config.neteaseApi || '', scope);
      player.init();
    }
  };
}
