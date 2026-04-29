import { $, $$, clamp } from '../../core/utils.js';
import { EventScope } from '../../core/runtime.js';
import { HitokotoTips } from './hitokoto.js';

class PomodoroTimer {
  constructor(config, tips, scope) {
    const cfg = config || {};
    this.focusMin    = clamp(parseInt(cfg.pomodoroFocus ?? 25, 10), 5, 90);
    this.shortMin    = clamp(parseInt(cfg.pomodoroShort ?? 5, 10), 1, 30);
    this.longMin     = clamp(parseInt(cfg.pomodoroLong ?? 15, 10), 5, 60);
    this.longAfter   = clamp(parseInt(cfg.pomodoroLongAfter ?? 4, 10), 2, 8);
    this.autoStart   = cfg.pomodoroAutoStart === true || cfg.pomodoroAutoStart === 'true';
    this.tips        = tips;
    this.scope       = scope;

    this.phase       = 'focus';
    this.running     = false;
    this.remaining   = this.focusMin * 60;
    this.total       = this.focusMin * 60;
    this.tomatoes    = this._loadTomatoes();
    this._timer      = null;
    this.stateKey    = 'pf-pom-state';
    this.notifyKey   = 'pf-pom-notify-optin';
    this.notifyOptIn = this._loadNotifyPreference();
    this._notifyButtonState = '';
    this._dotsDone = -1;
    this._dotsTotal = -1;

    this.timeEl      = $('#pf-pom-time');
    this.labelEl     = $('#pf-pom-label');
    this.progressEl  = $('.pf-pom-progress');
    this.startBtn    = $('#pf-pom-start');
    this.resetBtn    = $('#pf-pom-reset');
    this.skipBtn     = $('#pf-pom-skip');
    this.notifyBtn   = $('#pf-pom-notify');
    this.countEl     = $('#pf-pom-count');
    this.taskEl      = $('#pf-pom-task');
    this.statusEl    = $('#pf-pom-status');
    this.tabs        = $$('.pf-pom-tab');
    this.rootEl      = $('#pf-pomodoro');

    this.floatTime   = $('#pf-pom-float-time');
    this.floatDot    = $('#pf-pom-float-dot');
    this.floatLabel  = $('#pf-pom-float-label');
  }

  _phaseLabel(phase) {
    const phaseMap = { focus: '专注', short: '短休', long:  '长休' };
    return phaseMap[phase] || '专注';
  }

  _todayKey() {
    const d = new Date();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${month}-${day}`;
  }

  _loadTomatoes() {
    try {
      const saved = localStorage.getItem('pf-tomatoes');
      if (!saved) return 0;
      const obj = JSON.parse(saved);
      return obj.date === this._todayKey() ? (obj.count || 0) : 0;
    } catch (_) { return 0; }
  }

  _saveTomatoes() {
    try {
      localStorage.setItem('pf-tomatoes', JSON.stringify({
        date: this._todayKey(),
        count: this.tomatoes
      }));
    } catch (_) { }
  }

  _loadNotifyPreference() {
    try { return localStorage.getItem(this.notifyKey) === 'true'; } catch (_) { return false; }
  }

  _saveNotifyPreference() {
    try { localStorage.setItem(this.notifyKey, this.notifyOptIn ? 'true' : 'false'); } catch (_) { }
  }

  _announce(message) {
    if (!this.statusEl || !message) return;
    this.statusEl.textContent = '';
    window.setTimeout(() => {
      if (this.statusEl) this.statusEl.textContent = message;
    }, 30);
  }

  _updateNotifyButton() {
    if (!this.notifyBtn) return;
    const supported = 'Notification' in window;
    if (!supported) {
      if (this._notifyButtonState === 'unsupported') return;
      this.notifyBtn.hidden = true;
      this._notifyButtonState = 'unsupported';
      return;
    }

    const granted = Notification.permission === 'granted';
    const enabled = this.notifyOptIn && granted;
    const state = `${Notification.permission}:${enabled ? '1' : '0'}`;
    if (this._notifyButtonState === state) return;
    this.notifyBtn.hidden = false;
    this.notifyBtn.classList.toggle('active', enabled);
    this.notifyBtn.setAttribute('aria-pressed', enabled ? 'true' : 'false');
    this.notifyBtn.setAttribute('aria-label', enabled ? '关闭阶段提醒' : '启用阶段提醒');
    this.notifyBtn.title = enabled ? '关闭阶段提醒' : '启用阶段提醒';
    this._notifyButtonState = state;
  }

  toggleNotify() {
    if (!('Notification' in window)) {
      this._announce('当前浏览器不支持系统通知');
      return;
    }

    if (this.notifyOptIn && Notification.permission === 'granted') {
      this.notifyOptIn = false;
      this._saveNotifyPreference();
      this._updateNotifyButton();
      this._announce('已关闭阶段提醒');
      return;
    }

    if (Notification.permission === 'denied') {
      this.notifyOptIn = false;
      this._saveNotifyPreference();
      this._updateNotifyButton();
      this._announce('浏览器已阻止通知，请在浏览器设置中重新开启');
      return;
    }

    Notification.requestPermission().then(permission => {
      this.notifyOptIn = permission === 'granted';
      this._saveNotifyPreference();
      this._updateNotifyButton();
      if (permission === 'granted') {
        this._announce('已启用阶段提醒');
      } else if (permission === 'denied') {
        this._announce('浏览器已阻止通知提醒');
      } else {
        this._announce('暂未启用阶段提醒');
      }
    }).catch(() => {
      this.notifyOptIn = false;
      this._saveNotifyPreference();
      this._updateNotifyButton();
      this._announce('通知权限请求失败');
    });
  }

  _persistState() {
    try {
      const payload = {
        phase: this.phase,
        running: this.running,
        remaining: this.remaining,
        task: this.taskEl ? this.taskEl.value.slice(0, 60) : '',
        savedAt: Date.now()
      };
      localStorage.setItem(this.stateKey, JSON.stringify(payload));
    } catch (_) { }
  }

  _restoreState() {
    try {
      const raw = localStorage.getItem(this.stateKey);
      if (!raw) return false;
      const saved = JSON.parse(raw);
      const phase = ['focus', 'short', 'long'].includes(saved.phase) ? saved.phase : 'focus';
      this._switchPhase(phase);
      this.remaining = clamp(parseInt(saved.remaining ?? this.total, 10), 1, this.total);
      this.running = saved.running === true;
      if (this.taskEl && typeof saved.task === 'string') {
        this.taskEl.value = saved.task.slice(0, 60);
      }

      if (this.running) {
        const elapsed = Math.max(0, Math.floor((Date.now() - Number(saved.savedAt || Date.now())) / 1000));
        this._reconcileElapsed(elapsed);
        if (this.running) {
          this.endTime = Date.now() + this.remaining * 1000;
          this._timer = window.setInterval(() => this._tick(), 1000);
        }
      }

      this._persistState();
      return true;
    } catch (_) { return false; }
  }

  _reconcileElapsed(elapsed) {
    let remainingElapsed = elapsed;
    while (this.running && remainingElapsed > 0) {
      if (remainingElapsed < this.remaining) {
        this.remaining -= remainingElapsed;
        remainingElapsed = 0;
        break;
      }
      remainingElapsed -= this.remaining;
      this._advancePhase(false, '计时已恢复到最新状态');
    }
  }

  _notify(title, body) {
    if (this.notifyOptIn && 'Notification' in window && Notification.permission === 'granted') {
      try { new Notification(title, { body, icon: '' }); } catch (_) { }
    }
    document.title = `🍅 ${title} — ${document.title.replace(/^[🍅☕][^—]+—\s*/, '')}`;
    setTimeout(() => {
      document.title = document.title.replace(/^[🍅☕][^—]+—\s*/, '');
    }, 5000);
  }

  _formatTime(sec) {
    const m = Math.floor(sec / 60).toString().padStart(2, '0');
    const s = (sec % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
  }

  _restPhaseFor(completedFocuses = this.tomatoes + 1) {
    return completedFocuses % this.longAfter === 0 ? 'long' : 'short';
  }

  _updateRing() {
    if (!this.progressEl) return;
    const circumference = 502.65;
    const ratio = this.total > 0 ? this.remaining / this.total : 1;
    const offset = circumference * (1 - ratio);
    this.progressEl.style.strokeDashoffset = String(offset);
    this.progressEl.classList.toggle('phase-short', this.phase === 'short');
    this.progressEl.classList.toggle('phase-long', this.phase === 'long');

    const gradMap = { focus: 'url(#pf-grad-focus)', short: 'url(#pf-grad-short)', long: 'url(#pf-grad-long)' };
    this.progressEl.setAttribute('stroke', gradMap[this.phase] || gradMap.focus);
  }

  _updateDisplay() {
    const timeStr = this._formatTime(this.remaining);
    if (this.timeEl)  this.timeEl.textContent = timeStr;
    if (this.floatTime) this.floatTime.textContent = timeStr;

    const label = `${this._phaseLabel(this.phase)}中`;
    if (this.labelEl)   this.labelEl.textContent = this.running ? label : (this.phase === 'focus' ? '准备开始' : '休息时间');
    if (this.floatLabel) this.floatLabel.textContent = this.running ? label : this._phaseLabel(this.phase);

    if (this.startBtn) {
      this.startBtn.textContent = this.running ? '暂停' : '开始';
    }

    if (this.floatDot) {
      this.floatDot.classList.toggle('running', this.running);
    }

    this._updateRing();
    this._updateDots();
    this._updateNotifyButton();
  }

  _updateDots() {
    if (!this.countEl) return;
    const done = this.tomatoes % this.longAfter;
    if (this._dotsDone === done && this._dotsTotal === this.longAfter) {
      return;
    }

    const dots = [];
    for (let i = 0; i < this.longAfter; i++) {
      const dot = document.createElement('div');
      dot.className = 'pf-pom-dot' + (i < done ? ' done' : '');
      dots.push(dot);
    }
    this.countEl.replaceChildren(...dots);
    this._dotsDone = done;
    this._dotsTotal = this.longAfter;
  }

  _tick() {
    const now = Date.now();
    if (now >= this.endTime) {
      this.remaining = 0;
      this._complete();
    } else {
      this.remaining = Math.round((this.endTime - now) / 1000);
      this._updateDisplay();
      this._persistState();
    }
  }

  _advancePhase(shouldNotify, recoveryMessage = '') {
    if (this.phase === 'focus') {
      this.tomatoes++;
      this._saveTomatoes();
      const nextPhase = this._restPhaseFor(this.tomatoes);
      if (shouldNotify) {
        this._notify('专注完成！🍅', nextPhase === 'long' ? '好好休息一下，你赢得了长休！' : '稍作休息，继续加油！');
      }
      this._switchPhase(nextPhase);
      this.running = this.autoStart;
    } else {
      if (shouldNotify) {
        this._notify('休息结束', '新的专注阶段已经开始。');
      }
      this._switchPhase('focus');
      this.running = this.autoStart;
    }

    if (recoveryMessage) {
      this._announce(recoveryMessage);
    }
    if (this.tips) this.tips.setPhase(this.phase);
  }

  _complete() {
    clearInterval(this._timer);
    this._timer = null;
    this.running = false;
    this._advancePhase(true);
    if (this.running) {
      this.endTime = Date.now() + this.remaining * 1000;
      this._timer = window.setInterval(() => this._tick(), 1000);
    }
    this._updateDisplay();
    this._persistState();
  }

  _switchPhase(phase) {
    this.phase = phase;
    if (this.rootEl) {
      this.rootEl.setAttribute('data-phase', phase);
    }
    const mins = { focus: this.focusMin, short: this.shortMin, long: this.longMin };
    this.total = (mins[phase] || this.focusMin) * 60;
    this.remaining = this.total;

    this.tabs.forEach(t => {
      t.classList.toggle('active', t.dataset.phase === phase);
      t.setAttribute('aria-pressed', t.dataset.phase === phase ? 'true' : 'false');
    });
  }

  start() {
    if (this.running) return;
    this.running = true;
    this.endTime = Date.now() + this.remaining * 1000;
    this._timer = window.setInterval(() => this._tick(), 1000);
    this._announce(`${this._phaseLabel(this.phase)}计时已开始`);
    this._updateDisplay();
    this._persistState();
  }

  pause() {
    if (!this.running) return;
    clearInterval(this._timer);
    this._timer = null;
    this.running = false;
    this.remaining = Math.max(0, Math.round((this.endTime - Date.now()) / 1000));
    this._announce('计时已暂停');
    this._updateDisplay();
    this._persistState();
  }

  reset() {
    clearInterval(this._timer);
    this._timer = null;
    this.running = false;
    this.remaining = this.total;
    this._announce(`${this._phaseLabel(this.phase)}已重置`);
    this._updateDisplay();
    this._persistState();
  }

  toggleStart() {
    if (this.running) {
      this.pause();
    } else {
      this.start();
    }
  }

  selectPhase(phase) {
    if (this.running) {
      this._announce('计时进行中，请先暂停或使用跳过按钮。');
      return;
    }
    this._switchPhase(phase);
    this.reset();
    this._announce(`已切换到${this._phaseLabel(phase)}`);
    if (this.tips) this.tips.setPhase(phase);
  }

  skipPhase() {
    const nextPhase = this.phase === 'focus' ? 'short' : 'focus';
    const resolvedNextPhase = this.phase === 'focus' ? this._restPhaseFor(this.tomatoes + 1) : nextPhase;
    clearInterval(this._timer);
    this._timer = null;
    this.running = false;
    this._switchPhase(resolvedNextPhase);
    this.remaining = this.total;
    this._announce(`已跳到${this._phaseLabel(resolvedNextPhase)}`);
    if (this.tips) this.tips.setPhase(resolvedNextPhase);
    this._updateDisplay();
    this._persistState();
  }

  init() {
    this._switchPhase(this.phase);
    this._restoreState();
    this._updateDisplay();
    if (this.tips) this.tips.setPhase(this.phase);

    if (this.startBtn) this.scope.on(this.startBtn, 'click', () => this.toggleStart());
    if (this.resetBtn) this.scope.on(this.resetBtn, 'click', () => this.reset());
    if (this.notifyBtn) this.scope.on(this.notifyBtn, 'click', () => this.toggleNotify());
    if (this.taskEl) this.scope.on(this.taskEl, 'input', () => this._persistState());
    if (this.skipBtn) this.scope.on(this.skipBtn, 'click', () => this.skipPhase());

    this.tabs.forEach(tab => {
      this.scope.on(tab, 'click', () => {
        const phase = tab.dataset.phase;
        if (phase) this.selectPhase(phase);
      });
    });

    this.scope.on(window, 'beforeunload', () => this._persistState());
  }

}

export function pomodoroModule() {
  return {
    name: 'pomodoro',
    guard: () => !!$('#pf-pomodoro'),
    init: (ctx) => {
      const root = $('#pf-pomodoro');
      if (root.dataset.pfBound === '1') return null;
      root.dataset.pfBound = '1';

      const scope = new EventScope();
      const tipsEl = $('#pf-pom-tips-text');
      const tips = tipsEl ? new HitokotoTips(tipsEl, ctx.config.tipMode || 'local', ctx.config.focusTips || []) : null;

      const pom = new PomodoroTimer(ctx.config, tips, scope);
      pom.init();
    }
  };
}
