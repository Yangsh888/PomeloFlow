<?php
declare(strict_types=1);

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$opts         = $this->options;
$pomodoro     = pf_pomodoro_settings($opts);
$musicEnable  = $pomodoro['musicEnable'];
$neteaseApi   = pf_frontend_url(pf_text($opts->neteaseApi ?? ''), $opts);
$hasMusicList = pf_prepare_music_tracks($opts->musicList ?? '', $neteaseApi !== '', $opts) !== [];
$focusTime    = str_pad((string) $pomodoro['focus'], 2, '0', STR_PAD_LEFT) . ':00';
$focusTips    = pf_focus_tips();
$initialTip   = $focusTips[array_rand($focusTips)];
?>
<div class="pf-pomodoro" id="pf-pomodoro">
    <div class="pf-pom-tabs" role="group" aria-label="番茄钟模式">
        <button class="pf-pom-tab active" data-phase="focus" type="button" aria-pressed="true">专注</button>
        <button class="pf-pom-tab" data-phase="short" type="button" aria-pressed="false">短休</button>
        <button class="pf-pom-tab" data-phase="long"  type="button" aria-pressed="false">长休</button>
    </div>

    <div class="pf-pom-body">
        <input
            type="text"
            class="pf-pom-task"
            id="pf-pom-task"
            placeholder="请输入当前任务（选填）"
            maxlength="60"
            aria-label="当前任务"
        >

        <div class="pf-pom-ring-wrap">
            <svg class="pf-pom-svg" width="180" height="180" viewBox="0 0 180 180" aria-hidden="true">
                <defs>
                    <linearGradient id="pf-grad-focus" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%"   stop-color="#f07040"/>
                        <stop offset="100%" stop-color="#e8612a"/>
                    </linearGradient>
                    <linearGradient id="pf-grad-short" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%"   stop-color="#4ecba0"/>
                        <stop offset="100%" stop-color="#3dba8f"/>
                    </linearGradient>
                    <linearGradient id="pf-grad-long" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%"   stop-color="#7aabf0"/>
                        <stop offset="100%" stop-color="#5b8fd6"/>
                    </linearGradient>
                </defs>
                <circle class="pf-pom-track"    cx="90" cy="90" r="80"/>
                <circle class="pf-pom-progress" cx="90" cy="90" r="80"/>
            </svg>
            <div class="pf-pom-center">
                <div class="pf-pom-time" id="pf-pom-time" aria-live="polite" aria-atomic="true"><?php echo pf_esc_html($focusTime); ?></div>
                <div class="pf-pom-label" id="pf-pom-label">准备开始</div>
            </div>
        </div>

        <div class="pf-pom-count" id="pf-pom-count" aria-label="今日完成番茄数">
            <div class="pf-pom-dot"></div>
            <div class="pf-pom-dot"></div>
            <div class="pf-pom-dot"></div>
            <div class="pf-pom-dot"></div>
        </div>

        <div class="pf-pom-controls">
            <button class="pf-pom-btn pf-pom-btn-secondary" id="pf-pom-reset" type="button" aria-label="重置当前阶段" title="重置当前阶段">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 2.63-6.36L3 3" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 3v4h4" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>

            <button class="pf-pom-btn pf-pom-btn-primary" id="pf-pom-start" type="button">开始</button>

            <button class="pf-pom-btn pf-pom-btn-secondary" id="pf-pom-notify" type="button" aria-label="启用阶段提醒" aria-pressed="false" title="启用阶段提醒">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 17H5l1.2-1.6A3 3 0 0 0 6.8 13.6V10a5.2 5.2 0 1 1 10.4 0v3.6a3 3 0 0 0 .6 1.8L19 17h-4z" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 19a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round"/></svg>
            </button>

            <button class="pf-pom-btn pf-pom-btn-secondary" id="pf-pom-skip" type="button" aria-label="跳到下一阶段" title="跳到下一阶段">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4l10 8-10 8V4z" fill="currentColor"/><rect x="19" y="4" width="2" height="16" rx="1" fill="currentColor"/></svg>
            </button>
        </div>

        <div class="pf-pom-tips" role="status" aria-live="polite" aria-atomic="true">
            <div class="pf-pom-tips-text" id="pf-pom-tips-text"><?php echo pf_esc_html($initialTip); ?></div>
        </div>
        <div class="sr-only" id="pf-pom-status" aria-live="polite" aria-atomic="true"></div>

        <?php if ($musicEnable && $hasMusicList): ?>
        <div class="pf-music" id="pf-music">
            <button class="pf-music-bar" id="pf-music-bar" type="button" aria-label="展开音乐播放器" aria-expanded="false" aria-controls="pf-music-panel">
                <div class="pf-music-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18V5l12-2v13" stroke="#fff" stroke-width="1.75" fill="none" stroke-linecap="round" stroke-linejoin="round"/><circle cx="6" cy="18" r="3" stroke="#fff" stroke-width="1.75" fill="none"/><circle cx="18" cy="16" r="3" stroke="#fff" stroke-width="1.75" fill="none"/></svg>
                </div>
                <div class="pf-music-info">
                    <div class="pf-music-title" id="pf-music-title">待加载</div>
                    <div class="pf-music-artist" id="pf-music-artist"></div>
                    <div class="pf-music-status" id="pf-music-status" aria-live="polite" aria-atomic="true">待播放</div>
                </div>
                <div class="pf-music-toggle" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
            </button>
            <div class="pf-music-panel" id="pf-music-panel" aria-hidden="true" inert>
                <div class="pf-music-controls">
                    <button class="pf-music-ctrl" id="pf-music-prev" type="button" aria-label="上一首">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 20L9 12l10-8v16z" fill="currentColor"/><rect x="5" y="4" width="2" height="16" rx="1" fill="currentColor"/></svg>
                    </button>
                    <button class="pf-music-ctrl play-pause" id="pf-music-play" type="button" aria-label="播放/暂停">
                        <svg class="pf-icon-play" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3l14 9-14 9V3z" fill="currentColor"/></svg>
                        <svg class="pf-icon-pause" viewBox="0 0 24 24" aria-hidden="true" style="display:none"><rect x="6" y="4" width="4" height="16" rx="1.5" fill="currentColor"/><rect x="14" y="4" width="4" height="16" rx="1.5" fill="currentColor"/></svg>
                    </button>
                    <button class="pf-music-ctrl" id="pf-music-next" type="button" aria-label="下一首">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4l10 8-10 8V4z" fill="currentColor"/><rect x="19" y="4" width="2" height="16" rx="1" fill="currentColor"/></svg>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
