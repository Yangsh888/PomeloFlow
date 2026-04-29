<?php
declare(strict_types=1);

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$opts          = $this->options;
$isPost        = $this->is('post');
$title         = pf_text($this->title ?? '');
$authorName    = pf_text($this->author->screenName ?? $this->author->name ?? '');
$authorMail    = pf_text($this->author->mail    ?? '');
$siteUrl       = pf_frontend_url(pf_text($opts->siteUrl ?? ''), $opts);
$bannerUrl     = pf_frontend_url(pf_text($this->fields->bannerUrl ?? ''), $opts);
$directoryOn   = pf_text($this->fields->directoryOn ?? 'auto');
$pomodoroHide  = pf_bool($this->fields->pomodoroHide ?? 'false');

$showCopyright  = pf_bool($opts->showCopyright  ?? 'true');
$showAuthorCard = pf_bool($opts->showAuthorCard ?? 'true');
$selfIntro      = trim(pf_text($opts->selfIntro ?? ''));
$pomodoro       = pf_pomodoro_settings($opts);
$focusTime      = str_pad((string) $pomodoro['focus'], 2, '0', STR_PAD_LEFT) . ':00';
$showToc        = $directoryOn === 'on' || ($directoryOn !== 'off' && pf_has_toc_headings($this));
?>
<?php $this->need('public/header.php'); ?>

<article class="pf-post-wrap" itemscope itemtype="https://schema.org/Article">

    <header class="pf-post-header pf-animate-up">
        <?php if ($isPost): ?>
        <div class="pf-post-cats">
            <?php
            $categories = is_array($this->categories ?? null) ? $this->categories : [];
            if (!empty($categories)):
                foreach ($categories as $category):
                    $catName = pf_text($category['name'] ?? '');
                    if ($catName === '') continue;
                    $catUrl = pf_safe_url(pf_text($category['permalink'] ?? ''));
                    if ($catUrl !== ''):
            ?>
                <a class="pf-post-cat-tag" href="<?php echo pf_esc_attr($catUrl); ?>"><?php echo pf_esc_html($catName); ?></a>
            <?php   else: ?>
                <span class="pf-post-cat-tag"><?php echo pf_esc_html($catName); ?></span>
            <?php   endif;
                endforeach;
            endif;
            ?>
        </div>
        <?php endif; ?>

        <h1 class="pf-post-title" itemprop="name headline"><?php echo pf_esc_html($title); ?></h1>

        <div class="pf-post-meta">
            <a href="<?php $this->author->permalink(); ?>" itemprop="author"><?php echo pf_esc_html($authorName); ?></a>
            <span class="pf-meta-sep" aria-hidden="true"></span>
            <time itemprop="datePublished" datetime="<?php echo date('Y-m-d', (int) $this->created); ?>">
                <?php echo pf_format_date((int) $this->created); ?>
            </time>
            <?php if ($this->user->hasLogin()): ?>
                <span class="pf-meta-sep" aria-hidden="true"></span>
                <a href="<?php echo pf_esc_attr($opts->adminUrl . ($this->is('page') ? 'write-page.php' : 'write-post.php') . '?cid=' . (int) $this->cid); ?>" target="_blank" rel="noopener noreferrer">编辑</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($bannerUrl !== ''): ?>
    <div class="pf-post-banner">
        <img src="<?php echo pf_esc_attr($bannerUrl); ?>" alt="<?php echo pf_esc_attr($title); ?>" loading="lazy">
    </div>
    <?php endif; ?>

    <div class="pf-post-content" id="pf-post-content" itemprop="articleBody">
        <?php $this->content(); ?>
    </div>

    <?php if ($isPost): ?>
    <div class="pf-post-tags">
        <?php $this->tags('', true, ''); ?>
    </div>

    <?php if ($showCopyright): ?>
    <aside class="pf-copyright">
        <div class="pf-copyright-line"><strong>版权属于：</strong><a href="<?php echo pf_esc_attr($siteUrl); ?>"><?php $this->options->title(); ?></a></div>
        <div class="pf-copyright-line"><strong>本文链接：</strong><a href="<?php $this->permalink(); ?>" itemprop="url"><?php $this->permalink(); ?></a></div>
        <div class="pf-copyright-line">本作品采用 <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh" target="_blank" rel="noopener noreferrer">CC BY-NC-SA 4.0</a> 许可协议授权。</div>
    </aside>
    <?php endif; ?>

    <?php if ($showAuthorCard): ?>
    <div class="pf-author-card">
        <img
            class="pf-author-avatar"
            src="<?php echo pf_esc_attr(pf_avatar_url($authorMail)); ?>"
            alt="<?php echo pf_esc_attr($authorName); ?>"
            loading="lazy"
        >
        <div class="pf-author-info">
            <div class="pf-author-name">
                <a href="<?php $this->author->permalink(); ?>"><?php echo pf_esc_html($authorName); ?></a>
            </div>
            <div class="pf-author-bio">
                <?php echo $selfIntro !== '' ? pf_multiline_html($selfIntro) : '站点暂未填写介绍。'; ?>
            </div>
            <?php $socialLinks = pf_parse_social_links($opts->socialLinks ?? ''); ?>
            <?php if (!empty($socialLinks)): ?>
            <div class="pf-author-social pf-social-links">
                <?php foreach ($socialLinks as $social): ?>
                    <a class="pf-social-link" href="<?php echo pf_esc_attr($social['url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo pf_esc_attr($social['platform']); ?>" title="<?php echo pf_esc_attr($social['platform']); ?>">
                        <?php echo $social['icon']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <nav class="pf-post-nav" aria-label="上下篇文章">
        <?php
        $prevPost = pf_prev_post($this);
        $nextPost = pf_next_post($this);
        ?>
        <?php if ($prevPost): ?>
        <a class="pf-post-nav-item prev-post" href="<?php echo pf_esc_attr($prevPost['permalink']); ?>">
            <span class="pf-post-nav-dir">上一篇</span>
            <span class="pf-post-nav-title"><?php echo pf_esc_html($prevPost['title']); ?></span>
        </a>
        <?php else: ?>
        <div class="pf-post-nav-item prev-post is-disabled" aria-disabled="true">
            <span class="pf-post-nav-dir">上一篇</span>
            <span class="pf-post-nav-title">当前已是第一篇文章</span>
        </div>
        <?php endif; ?>

        <?php if ($nextPost): ?>
        <a class="pf-post-nav-item next-post" href="<?php echo pf_esc_attr($nextPost['permalink']); ?>">
            <span class="pf-post-nav-dir">下一篇</span>
            <span class="pf-post-nav-title"><?php echo pf_esc_html($nextPost['title']); ?></span>
        </a>
        <?php else: ?>
        <div class="pf-post-nav-item next-post is-disabled" aria-disabled="true">
            <span class="pf-post-nav-dir">下一篇</span>
            <span class="pf-post-nav-title">当前已是最后一篇文章</span>
        </div>
        <?php endif; ?>
    </nav>
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
