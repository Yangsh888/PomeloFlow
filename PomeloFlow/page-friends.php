<?php
/**
 * 友情链接
 *
 * @package custom
 */
declare(strict_types=1);

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$opts          = $this->options;
$title         = pf_text($this->title ?? '');
$authorName    = pf_text($this->author->screenName ?? $this->author->name ?? '');
$bannerUrl     = pf_frontend_url(pf_text($this->fields->bannerUrl ?? ''), $opts);
$directoryOn   = pf_text($this->fields->directoryOn ?? 'auto');
$pomodoroHide  = pf_bool($this->fields->pomodoroHide ?? 'false');

$pomodoro       = pf_pomodoro_settings($opts);
$focusTime      = str_pad((string) $pomodoro['focus'], 2, '0', STR_PAD_LEFT) . ':00';
$showToc        = $directoryOn === 'on' || ($directoryOn !== 'off' && pf_has_toc_headings($this));

$friendLinks    = pf_parse_friend_links($opts->friendLinks ?? '');
?>
<?php $this->need('public/header.php'); ?>

<article class="pf-post-wrap pf-friends-page" itemscope itemtype="https://schema.org/Article">
    <header class="pf-post-header pf-animate-up">
        <h1 class="pf-post-title" itemprop="name headline"><?php echo pf_esc_html($title); ?></h1>
        <div class="pf-post-meta">
            <a href="<?php $this->author->permalink(); ?>" itemprop="author"><?php echo pf_esc_html($authorName); ?></a>
            <span class="pf-meta-sep" aria-hidden="true"></span>
            <time itemprop="datePublished" datetime="<?php echo date('Y-m-d', (int) $this->created); ?>">
                <?php echo pf_format_date((int) $this->created); ?>
            </time>
            <?php if ($this->user->hasLogin()): ?>
                <span class="pf-meta-sep" aria-hidden="true"></span>
                <a href="<?php echo pf_esc_attr($opts->adminUrl . 'write-page.php?cid=' . (int) $this->cid); ?>" target="_blank" rel="noopener noreferrer">编辑</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($bannerUrl !== ''): ?>
    <div class="pf-post-banner pf-animate-up">
        <img src="<?php echo pf_esc_attr($bannerUrl); ?>" alt="<?php echo pf_esc_attr($title); ?>" loading="lazy">
    </div>
    <?php endif; ?>

    <div class="pf-post-content pf-animate-up" id="pf-post-content" itemprop="articleBody">
        <?php $this->content(); ?>
    </div>

    <?php if (!empty($friendLinks)): ?>
    <div class="pf-friend-grid pf-animate-up">
        <?php foreach ($friendLinks as $friend): ?>
        <a class="pf-friend-card" href="<?php echo pf_esc_attr($friend['url']); ?>" target="_blank" rel="noopener noreferrer">
            <img class="pf-friend-avatar" src="<?php echo pf_esc_attr($friend['avatar']); ?>" alt="<?php echo pf_esc_attr($friend['name']); ?>" loading="lazy">
            <div class="pf-friend-info">
                <div class="pf-friend-name" title="<?php echo pf_esc_attr($friend['name']); ?>"><?php echo pf_esc_html($friend['name']); ?></div>
                <div class="pf-friend-desc" title="<?php echo pf_esc_attr($friend['desc']); ?>"><?php echo pf_esc_html($friend['desc']); ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php $this->need('public/comments.php'); ?>
</article>

<?php if ($showToc): ?>
<button
    class="pf-toc-toggle"
    id="pf-toc-toggle"
    type="button"
    aria-label="打开文章目录"
    aria-controls="pf-toc"
    aria-expanded="false"
    title="展开目录"
>
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14M5 12h14M5 17h9" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round"/></svg>
    <span>目录</span>
</button>
<aside
    class="pf-toc<?php echo $directoryOn === 'on' ? ' force-show' : ''; ?>"
    id="pf-toc"
    aria-labelledby="pf-toc-title"
>
    <div class="pf-toc-inner">
        <div class="pf-toc-label" id="pf-toc-title">目录</div>
        <div class="pf-toc-list" id="pf-toc-list"></div>
    </div>
</aside>
<?php endif; ?>

<?php if (!$pomodoroHide && pf_bool($opts->pomodoroEnable ?? 'true')): ?>
<div class="pf-pom-float" id="pf-pom-float">
    <button class="pf-pom-float-btn" id="pf-pom-float-btn" type="button" aria-label="打开番茄钟" aria-haspopup="dialog">
        <div class="pf-pom-float-dot" id="pf-pom-float-dot"></div>
        <span class="pf-pom-float-time" id="pf-pom-float-time"><?php echo pf_esc_html($focusTime); ?></span>
        <span class="pf-pom-float-label" id="pf-pom-float-label">专注</span>
    </button>
</div>
<div class="pf-pom-float-panel pf-pom-float-panel-post" id="pf-pom-float-panel" role="dialog" aria-modal="true" aria-hidden="true" aria-label="番茄钟" inert>
    <?php $this->need('component/pomodoro.php'); ?>
</div>
<?php endif; ?>

<?php $this->need('public/footer.php'); ?>
