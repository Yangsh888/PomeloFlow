<?php
declare(strict_types=1);

/**
 * 一款轻盈专注的 Typecho 主题，融合阅读、写作与番茄钟体验。
 *
 * @package PomeloFlow
 * @author Yangsh888
 * @version 1.0.0
 * @link https://github.com/Yangsh888/PomeloFlow
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$pomodoroEnabled = pf_bool($this->options->pomodoroEnable ?? 'true');
$selfIntro = trim(pf_text($this->options->selfIntro ?? ''));
$pomodoro = pf_pomodoro_settings($this->options);
$focusTime = str_pad((string) $pomodoro['focus'], 2, '0', STR_PAD_LEFT) . ':00';
?>
<?php $this->need('public/header.php'); ?>

<div class="pf-home pf-container<?php echo !$pomodoroEnabled ? ' pf-home-no-sidebar' : ''; ?>">
    <?php if ($pomodoroEnabled): ?>
    <aside class="pf-home-sidebar">
        <?php $this->need('component/pomodoro.php'); ?>
    </aside>
    <?php endif; ?>
    <section class="pf-home-content">
        <?php if ($selfIntro !== ''): ?>
        <div class="pf-intro pf-animate-up">
            <p class="pf-intro-text"><?php echo pf_multiline_html($selfIntro); ?></p>
        </div>
        <?php endif; ?>
        <?php $this->need('component/post-list.php'); ?>
        <?php $this->need('component/pagination.php'); ?>
    </section>
</div>

<?php if ($pomodoroEnabled): ?>
<div class="pf-pom-float" id="pf-pom-float">
    <button class="pf-pom-float-btn" id="pf-pom-float-btn" type="button" aria-label="打开番茄钟" aria-haspopup="dialog">
        <div class="pf-pom-float-dot" id="pf-pom-float-dot"></div>
        <span class="pf-pom-float-time" id="pf-pom-float-time"><?php echo pf_esc_html($focusTime); ?></span>
        <span class="pf-pom-float-label" id="pf-pom-float-label">专注</span>
    </button>
</div>
<div class="pf-pom-float-panel" id="pf-pom-float-panel" role="dialog" aria-modal="true" aria-hidden="true" aria-label="番茄钟" inert></div>
<?php endif; ?>

<?php $this->need('public/footer.php'); ?>
