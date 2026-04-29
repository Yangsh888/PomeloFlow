<?php
declare(strict_types=1);

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

pf_security_headers();

$opts        = $this->options;
$siteTitle   = pf_text($opts->title ?? '');
$safeSiteUrl = pf_frontend_url(pf_text($opts->siteUrl ?? ''), $opts);
$themeUrl    = rtrim(pf_text($opts->themeUrl ?? ''), '/');
$logoUrl     = pf_frontend_url(pf_text($opts->logoUrl ?? ''), $opts);
$faviconUrl  = pf_frontend_url(pf_text($opts->faviconUrl ?? ''), $opts);
$colorSchema = pf_text($opts->colorSchema ?? 'auto');
$canToggleTheme = $colorSchema === 'auto';
$searchPattern = pf_search_pattern($opts);
$searchKeywords = pf_search_keywords($this);

$pfConfig = [
    'colorSchema'       => $colorSchema,
    'searchPattern'     => $searchPattern,
];

$currentPage = (int) $this->request->filter('int')->get('page', 1);

$navItems = [];
$pages = $this->widget('\Widget\Contents\Page\Rows');
while ($pages->next()) {
    $navItems[] = [
        'permalink' => pf_safe_url(pf_text($pages->permalink)),
        'title'     => pf_text($pages->title),
    ];
}

$isContent = $this->is('post') || $this->is('page');
$bodyClass = '';
if ($isContent)            $bodyClass = 'pf-post-page';
elseif ($this->is('index')) $bodyClass = 'pf-home-page';

if ($this->is('index') || $isContent) {
    $pomodoro = pf_pomodoro_settings($opts);
    $neteaseApi = pf_frontend_url(pf_text($opts->neteaseApi ?? ''), $opts);
    $pfConfig += [
        'pomodoroFocus'     => $pomodoro['focus'],
        'pomodoroShort'     => $pomodoro['short'],
        'pomodoroLong'      => $pomodoro['long'],
        'pomodoroLongAfter' => $pomodoro['longAfter'],
        'pomodoroAutoStart' => $pomodoro['autoStart'],
        'musicEnable'       => $pomodoro['musicEnable'],
        'neteaseApi'        => $neteaseApi,
        'tipMode'           => pf_text($opts->tipMode ?? 'local'),
        'focusTips'         => pf_focus_tips(),
        'musicTracks'       => pf_prepare_music_tracks($opts->musicList ?? '', $neteaseApi !== '', $opts),
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-CN" <?php
    if ($colorSchema === 'dark')  echo 'data-theme="dark"';
    elseif ($colorSchema === 'light') echo 'data-theme="light"';
?>>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="<?php echo pf_esc_attr($colorSchema === 'dark' ? '#161b22' : '#fffaf5'); ?>">

    <?php if ($faviconUrl !== ''): ?>
        <link rel="shortcut icon" href="<?php echo pf_esc_attr($faviconUrl); ?>">
    <?php elseif ($safeSiteUrl !== ''): ?>
        <link rel="shortcut icon" href="<?php echo pf_esc_attr(rtrim($safeSiteUrl, '/') . '/favicon.ico'); ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="<?php echo pf_esc_attr($themeUrl . '/assets/css/pomeloflow.css'); ?>">

    <script>
        /* 防止浅/深色闪烁：在渲染前设置 data-theme */
        (function () {
            var schema = <?php echo pf_json_encode($colorSchema); ?>;
            var html = document.documentElement;
            var themeColorMeta = document.querySelector('meta[name="theme-color"]');
            var saved = null;
            var theme = schema;
            var setThemeColor = function (theme) {
                if (!themeColorMeta) return;
                themeColorMeta.setAttribute('content', theme === 'dark' ? '#161b22' : '#fffaf5');
            };
            try {
                saved = localStorage.getItem('pf-theme');
            } catch (_) {}
            if (theme !== 'dark' && theme !== 'light') {
                theme = (saved === 'dark' || saved === 'light')
                    ? saved
                    : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            }
            html.setAttribute('data-theme', theme);
            setThemeColor(theme);
        })();
        window.PF_CONFIG = <?php echo pf_json_encode($pfConfig); ?>;
    </script>

    <?php $this->header('generator=&template=&pingback=&xmlrpc=&wlw=&commentReply=&antiSpam='); ?>

    <title>
        <?php if ($currentPage > 1) echo '第 ' . $currentPage . ' 页 - '; ?>
        <?php $this->archiveTitle(
            [
                'category' => '分类 %s',
                'search'   => '搜索 %s',
                'tag'      => '标签 %s',
                'author'   => '%s 的文章',
            ],
            '',
            ' - '
        ); ?>
        <?php $this->options->title(); ?>
    </title>
</head>
<body<?php if ($bodyClass !== '') echo ' class="' . pf_esc_attr($bodyClass) . '"'; ?>>

<a class="pf-skip-link" href="#pf-main">跳到正文</a>

<?php if ($isContent): ?>
    <div class="pf-reading-bar" id="pf-reading-bar" aria-hidden="true"></div>
<?php endif; ?>

<header class="pf-nav" id="pf-nav" role="banner">
    <div class="pf-nav-inner">
        <a class="pf-nav-brand" href="<?php echo pf_esc_attr($safeSiteUrl); ?>" aria-label="<?php echo pf_esc_attr($siteTitle); ?> 首页">
            <?php if ($logoUrl !== ''): ?>
                <img src="<?php echo pf_esc_attr($logoUrl); ?>" alt="<?php echo pf_esc_attr($siteTitle); ?>">
            <?php else: ?>
                <span class="pf-nav-brand-dot" aria-hidden="true"></span>
                <?php echo pf_esc_html($siteTitle); ?>
            <?php endif; ?>
        </a>

        <nav class="pf-nav-links" aria-label="主导航">
            <?php foreach ($navItems as $item): ?>
                <a class="pf-nav-link" href="<?php echo pf_esc_attr($item['permalink']); ?>"><?php echo pf_esc_html($item['title']); ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="pf-nav-tools" role="toolbar" aria-label="工具栏">
            <button class="pf-nav-btn" id="pf-search-btn" aria-label="搜索" aria-haspopup="dialog" aria-expanded="false" aria-controls="pf-search-overlay" title="搜索" type="button">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.75" fill="none"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
            </button>

            <?php if ($canToggleTheme): ?>
            <button class="pf-nav-btn" id="pf-theme-btn" aria-label="切换深色模式" title="切换深色模式" type="button">
                <svg class="pf-icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round"/></svg>
                <svg class="pf-icon-sun" viewBox="0 0 24 24" aria-hidden="true" style="display:none"><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.75" fill="none"/><path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
            </button>
            <?php endif; ?>

            <button class="pf-hamburger" id="pf-hamburger" aria-label="展开菜单" aria-expanded="false" aria-controls="pf-mobile-menu" type="button">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</header>

<div class="pf-search-overlay" id="pf-search-overlay" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="pf-search-title" aria-describedby="pf-search-hint" inert>
    <div class="pf-search-box">
        <h2 class="sr-only" id="pf-search-title">站内搜索</h2>
        <form
            class="pf-search-input-wrap"
            method="get"
            action="<?php echo pf_esc_attr(pf_safe_url(pf_text($opts->index ?? $safeSiteUrl))); ?>"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5" stroke-linecap="round"/></svg>
            <input
                class="pf-search-input"
                id="pf-search-input"
                type="search"
                name="s"
                placeholder="搜索文章…"
                autocomplete="off"
                value="<?php echo pf_esc_attr($searchKeywords); ?>"
                aria-label="搜索关键词"
            >
            <button type="button" class="pf-search-close-btn" id="pf-search-close" aria-label="关闭搜索">
                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
            </button>
        </form>
        <div class="pf-search-hint" id="pf-search-hint">按 Enter 搜索 &nbsp;·&nbsp; 按 Esc 关闭</div>
        <div class="pf-search-status" id="pf-search-status" aria-live="polite"></div>
    </div>
</div>

<nav class="pf-mobile-menu" id="pf-mobile-menu" aria-label="移动端导航" aria-hidden="true" inert>
    <?php foreach ($navItems as $item): ?>
        <a href="<?php echo pf_esc_attr($item['permalink']); ?>"><?php echo pf_esc_html($item['title']); ?></a>
    <?php endforeach; ?>
</nav>

<main class="pf-main" id="pf-main" tabindex="-1">
