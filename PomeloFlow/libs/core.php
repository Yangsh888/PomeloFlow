<?php
declare(strict_types=1);

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

define('PF_EXCERPT_LEN',   120);
define('PF_MAX_URL_LEN',  2048);
define('PF_MAX_MUSIC',      50);

function pf_text(mixed $value, string $default = ''): string
{
    if ($value === null) {
        return $default;
    }
    if (is_bool($value)) {
        return $value ? '1' : '';
    }
    if (is_scalar($value)) {
        return (string) $value;
    }
    if (is_object($value) && method_exists($value, '__toString')) {
        return (string) $value;
    }
    return $default;
}

function pf_bool(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }
    return in_array(strtolower(trim(pf_text($value))), ['1', 'true', 'on', 'yes'], true);
}

function pf_int(mixed $value, int $default = 0): int
{
    if (is_int($value)) {
        return $value;
    }
    $text = trim(pf_text($value));
    return is_numeric($text) ? (int) $text : $default;
}

function pf_int_in_range(mixed $value, int $default, int $min, int $max): int
{
    return max($min, min($max, pf_int($value, $default)));
}

function pf_validate_int_range(mixed $value, int $min, int $max): bool
{
    $text = trim(pf_text($value));
    if ($text === '' || filter_var($text, FILTER_VALIDATE_INT) === false) {
        return false;
    }

    $number = (int) $text;
    return $number >= $min && $number <= $max;
}

function pf_validate_optional_url(mixed $value): bool
{
    $text = trim(pf_text($value));
    return $text === '' || pf_safe_url($text) !== '';
}

/**
 * 将多行文本拆分为非空行数组
 */
function pf_lines(mixed $value): array
{
    $text = trim(pf_text($value));
    if ($text === '') {
        return [];
    }
    $lines = preg_split('/\R/u', $text);
    if ($lines === false) {
        return [];
    }
    $result = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line !== '') {
            $result[] = $line;
        }
    }
    return $result;
}

function pf_esc_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', true);
}

function pf_esc_attr(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', true);
}

function pf_json_encode(mixed $value): string
{
    try {
        $json = json_encode(
            $value,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_HEX_AMP
            | JSON_INVALID_UTF8_SUBSTITUTE
            | JSON_THROW_ON_ERROR
        );
    } catch (\Throwable $e) {
        pf_report('json_encode', $e);
        return 'null';
    }

    return is_string($json) ? $json : 'null';
}

function pf_multiline_html(mixed $value): string
{
    $text = trim(pf_text($value));
    return $text === '' ? '' : nl2br(pf_esc_html($text));
}

function pf_safe_url(string $url): string
{
    $url = trim($url);
    if ($url === '' || strlen($url) > PF_MAX_URL_LEN) {
        return '';
    }

    if (str_starts_with($url, '//')) {
        return '';
    }

    $parts = parse_url($url);
    if ($parts === false) {
        return '';
    }
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if ($scheme !== '' && !in_array($scheme, ['http', 'https'], true)) {
        return '';
    }
    $filtered = filter_var($url, FILTER_SANITIZE_URL);
    return $filtered !== false ? $filtered : '';
}

function pf_frontend_url(string $url, ?object $options = null): string
{
    $safe = pf_safe_url($url);
    if ($safe === '' || $options === null) {
        return $safe;
    }

    $siteUrl = strtolower(rtrim(pf_text($options->siteUrl ?? ''), '/'));
    if (!str_starts_with($siteUrl, 'https://')) {
        return $safe;
    }

    return str_starts_with(strtolower($safe), 'http://')
        ? 'https://' . substr($safe, 7)
        : $safe;
}

function pf_default_avatar_url(?object $options = null): string
{
    $themeUrl = '';
    if ($options !== null) {
        $themeUrl = rtrim(pf_text($options->themeUrl ?? ''), '/');
    }

    return $themeUrl !== ''
        ? $themeUrl . '/assets/img/avatar.svg'
        : '/usr/themes/PomeloFlow/assets/img/avatar.svg';
}

function pf_normalize_social_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (str_starts_with(strtolower($url), 'mailto:')) {
        $mail = trim(substr($url, 7));
        return filter_var($mail, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $mail : '';
    }

    if (filter_var($url, FILTER_VALIDATE_EMAIL)) {
        return 'mailto:' . $url;
    }

    if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }

    return pf_safe_url($url);
}

function pf_validate_social_links(mixed $raw): bool
{
    foreach (pf_lines($raw) as $line) {
        $parts = explode('||', $line, 2);
        if (count($parts) !== 2) {
            return false;
        }

        $platform = strtolower(trim($parts[0]));
        if ($platform === '' || pf_normalize_social_url($parts[1]) === '') {
            return false;
        }
    }

    return true;
}

function pf_validate_friend_links(mixed $raw): bool
{
    foreach (pf_lines($raw) as $line) {
        $parts = explode('||', $line, 4);
        if (count($parts) < 2) {
            return false;
        }

        $name = trim($parts[0]);
        $url = pf_normalize_social_url($parts[1]);
        if ($name === '' || $url === '' || str_starts_with($url, 'mailto:')) {
            return false;
        }

        if (isset($parts[3]) && trim($parts[3]) !== '' && !pf_validate_optional_url($parts[3])) {
            return false;
        }
    }

    return true;
}

/**
 * @return list<string>
 */
function pf_focus_tips(): array
{
    return [
        '专注是一种能力，也是一种礼物。',
        '休息是为了走更长的路。',
        '每一个番茄，都是对自己的一次承诺。',
        '深吸一口气，把注意力带回当下。',
        '不被打扰的时光，是最奢侈的资产。',
        '伟大的事情，都由一段段专注的时间堆积而成。',
        '休息好了，思维才会清晰。',
        '此刻，只需要关注这一件事。',
        '慢下来，让好奇心引领你。',
        '把手机翻过来，世界不会因此停止转动。',
    ];
}

function pf_search_pattern(object $options): string
{
    $placeholder = '__PF_KEYWORDS__';

    try {
        $prefix = pf_text($options->index ?? $options->siteUrl ?? '');
        $pattern = \Typecho\Router::url('search', ['keywords' => $placeholder], $prefix);
        if ($pattern !== '' && str_contains($pattern, $placeholder)) {
            return $pattern;
        }
    } catch (\Throwable $e) {
        pf_report('search_pattern', $e);
    }

    $siteUrl = rtrim(pf_text($options->siteUrl ?? ''), '/');
    return $siteUrl . '/search/' . $placeholder . '/';
}

function pf_search_keywords(object $widget): string
{
    if (!isset($widget->request)) {
        return '';
    }

    try {
        $request = $widget->request->filter('search');
        $keywords = trim(pf_text($request->get('keywords', '')));
        if ($keywords !== '') {
            return $keywords;
        }

        return trim(pf_text($request->get('s', '')));
    } catch (\Throwable $e) {
        pf_report('search_keywords', $e);
        return '';
    }
}

function pf_report(string $scope, \Throwable $e): void
{
    error_log('[PomeloFlow] ' . $scope . ': ' . $e->getMessage());
}

function pf_security_headers(): void
{
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), geolocation=(), microphone=(), payment=(), usb=()');
    }
}

function pf_comment_require_url(object $options): bool
{
    if (isset($options->commentsRequireUrl)) {
        return pf_bool($options->commentsRequireUrl);
    }
    if (isset($options->commentsRequireURL)) {
        return pf_bool($options->commentsRequireURL);
    }
    return false;
}

/**
 * 获取评论父层作者名，构造 @mention
 */
function pf_reply_prefix(int $cid, int $parent, string $content): string
{
    static $authorCache = [];

    $content = trim($content);
    if ($parent <= 0) {
        return $content;
    }

    if ($cid > 0 && !isset($authorCache[$cid])) {
        $authorCache[$cid] = [];

        try {
            $db = \Typecho\Db::get();
            foreach ($db->fetchAll(
                $db->select('coid', 'author')
                    ->from('table.comments')
                    ->where('cid = ?', $cid)
            ) as $row) {
                $coid = (int) ($row['coid'] ?? 0);
                if ($coid > 0) {
                    $authorCache[$cid][$coid] = trim(pf_text($row['author'] ?? ''));
                }
            }
        } catch (\Throwable $e) {
            pf_report('reply_prefix', $e);
        }
    }

    if (isset($authorCache[$cid]) && array_key_exists($parent, $authorCache[$cid])) {
        $author = $authorCache[$cid][$parent];
        return $author === ''
            ? $content
            : pf_prepend_reply_mention($author, $content);
    }

    try {
        $db = \Typecho\Db::get();
        $row = $db->fetchRow(
            $db->select('author')
                ->from('table.comments')
                ->where('coid = ?', $parent)
                ->limit(1)
        );
        $author = trim(pf_text($row['author'] ?? ''));
        if ($cid > 0) {
            $authorCache[$cid][$parent] = $author;
        }
        if ($author === '') {
            return $content;
        }
        return pf_prepend_reply_mention($author, $content);
    } catch (\Throwable $e) {
        pf_report('reply_prefix', $e);
        return $content;
    }
}

function pf_prepend_reply_mention(string $author, string $content): string
{
    $mention = '<span class="pf-reply-mention">@' . pf_esc_html($author) . '</span>';
    if (preg_match('/^\s*<p(\b[^>]*)>/iu', $content)) {
        return (string) preg_replace('/^\s*<p(\b[^>]*)>/iu', '<p$1>' . $mention . ' ', $content, 1);
    }

    return $mention . ' ' . $content;
}

function pf_avatar_url(mixed $mail): string
{
    try {
        $options  = \Typecho\Widget::widget('\Widget\Options');
        $fallback = pf_default_avatar_url($options);

        $mail = trim(pf_text($mail));
        if ($mail === '') {
            return $fallback;
        }
        $gravatar = \Typecho\Common::gravatarUrl($mail, 80, 'G', null, true);
        $url      = pf_safe_url(pf_text($gravatar));
        return $url !== '' ? $url : $fallback;
    } catch (\Throwable $e) {
        pf_report('avatar_url', $e);
        return pf_default_avatar_url();
    }
}

/**
 * 获取文章封面图 URL（自定义字段 → 正文首图 → 随机库 → 空字符串）
 */
function pf_thumb(object $archive, object $options): string
{
    $banner = pf_frontend_url(pf_text($archive->fields->bannerUrl ?? ''), $options);
    if ($banner !== '') {
        return $banner;
    }

    $source = pf_text($archive->content ?? '');
    if ($source === '') {
        $source = pf_text($archive->text ?? '');
    }

    $firstImage = pf_extract_first_image_url($source);
    if ($firstImage !== '') {
        return $firstImage;
    }

    return pf_random_thumb($options);
}

/**
 * 从原始正文中提取第一张图片，避免触发完整内容渲染链
 */
function pf_extract_first_image_url(string $raw): string
{
    if ($raw === '') {
        return '';
    }

    if (preg_match('/<img[^>]+src\s*=\s*(["\'])(.*?)\1/i', $raw, $htmlMatch)) {
        $src = pf_safe_url((string) ($htmlMatch[2] ?? ''));
        if ($src !== '') {
            return $src;
        }
    }

    if (preg_match('/!\[[^\]]*]\(([^)\s]+)(?:\s+"[^"]*")?\)/', $raw, $markdownMatch)) {
        $src = pf_safe_url((string) ($markdownMatch[1] ?? ''));
        if ($src !== '') {
            return $src;
        }
    }

    return '';
}

function pf_random_thumb(object $options): string
{
    $thumbs = pf_lines($options->indexThumbs ?? '');
    if (!empty($thumbs)) {
        return pf_frontend_url($thumbs[array_rand($thumbs)], $options);
    }
    return '';
}

/**
 * 获取文章摘要（自定义字段 → excerpt() → 空字符串）
 * 返回纯文本，已做 HTML 转义
 */
function pf_excerpt(object $archive, int $length = PF_EXCERPT_LEN): string
{
    $custom = trim(pf_text($archive->fields->articleDesc ?? ''));
    if ($custom !== '') {
        return pf_esc_html($custom);
    }

    ob_start();
    try {
        $archive->excerpt($length, '');
    } catch (\Throwable) {
        ob_end_clean();
        return '';
    }
    $raw = (string) ob_get_clean();
    return trim(strip_tags($raw));
}

function pf_has_toc_headings(object $archive): bool
{
    $content = (string) ($archive->content ?? '');
    if ($content === '') {
        return false;
    }

    $hasTextHeading = static function (string $pattern) use ($content): bool {
        if (!preg_match_all($pattern, $content, $matches)) {
            return false;
        }

        foreach (($matches[2] ?? []) as $heading) {
            $text = trim(strip_tags(html_entity_decode((string) $heading, ENT_QUOTES, 'UTF-8')));
            if ($text !== '') {
                return true;
            }
        }

        return false;
    };

    if ($hasTextHeading('/<h([2-5])\b[^>]*>(.*?)<\/h\1>/isu')) {
        return true;
    }

    return $hasTextHeading('/<h1\b[^>]*>(.*?)<\/h1>/isu');
}

/**
 * @return array{title:string,permalink:string}|null
 */
function pf_prev_post(object $widget): ?array
{
    return _pf_adjacent_post($widget, 'prev');
}

/**
 * @return array{title:string,permalink:string}|null
 */
function pf_next_post(object $widget): ?array
{
    return _pf_adjacent_post($widget, 'next');
}

function _pf_adjacent_post(object $widget, string $dir): ?array
{
    try {
        $link = pf_capture_output(static function () use ($widget, $dir): void {
            if ($dir === 'prev') {
                $widget->thePrev('%s', '');
            } else {
                $widget->theNext('%s', '');
            }
        });

        if ($link === '' || !preg_match('/<a\b[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/is', $link, $matches)) {
            return null;
        }

        $permalink = pf_safe_url(html_entity_decode((string) ($matches[1] ?? ''), ENT_QUOTES, 'UTF-8'));
        $title = trim(strip_tags(html_entity_decode((string) ($matches[2] ?? ''), ENT_QUOTES, 'UTF-8')));
        if ($permalink === '' || $title === '') {
            return null;
        }
        return ['title' => $title, 'permalink' => $permalink];
    } catch (\Throwable $e) {
        pf_report('adjacent_post', $e);
        return null;
    }
}

/**
 * 将 Unix 时间戳格式化为带空格的中文日期
 * 例：2026 年 7 月 1 日
 */
function pf_format_date(int $ts): string
{
    return date('Y', $ts) . ' 年 ' . ltrim(date('m', $ts), '0') . ' 月 ' . ltrim(date('d', $ts), '0') . ' 日';
}

function pf_capture_output(callable $callback): string
{
    ob_start();
    $callback();
    return trim((string) ob_get_clean());
}

function pf_archive_context(object $widget): array
{
    $siteUrl = pf_safe_url(pf_text($widget->options->siteUrl ?? ''));
    $keywords = pf_search_keywords($widget);

    $title = '';
    $desc = '';
    $label = '归档';
    $listTitle = '文章列表';
    $emptyTitle = '暂时还没有文章';
    $emptyDesc = '相关内容正在整理中，请稍后再来查看。';
    $emptyActionText = '';
    $emptyActionUrl = '';

    if ($widget->is('index')) {
        $label = '最新内容';
        $title = '最近更新';
        $desc = '保持专注，也保持好奇心。';
        $listTitle = '最新文章';
    } elseif ($widget->is('category')) {
        $label = '分类';
        $title = pf_capture_output(static fn() => $widget->archiveTitle());
        $desc = pf_capture_output(static fn() => $widget->archiveDescription());
        $listTitle = $title !== '' ? $title . ' 分类' : '分类文章';
        $emptyTitle = '这个分类下还没有文章';
        $emptyDesc = '可以稍后再来看看，或者先浏览其他内容。';
        $emptyActionText = $siteUrl !== '' ? '返回首页' : '';
        $emptyActionUrl = $siteUrl;
    } elseif ($widget->is('tag')) {
        $label = '标签';
        $title = pf_capture_output(static fn() => $widget->archiveTitle([], '#'));
        $listTitle = $title !== '' ? $title . ' 标签' : '标签文章';
        $desc = '与这个主题相关的内容，会继续在这里沉淀。';
        $emptyTitle = '这个标签下还没有文章';
        $emptyDesc = '换个相关主题看看，也许会有新的发现。';
        $emptyActionText = $siteUrl !== '' ? '返回首页' : '';
        $emptyActionUrl = $siteUrl;
    } elseif ($widget->is('search')) {
        $label = '搜索结果';
        $title = $keywords !== '' ? '「' . $keywords . '」' : '搜索结果';
        $listTitle = $keywords !== '' ? '搜索「' . $keywords . '」' : '搜索结果';
        $desc = $keywords !== '' ? '没有理想结果时，试试更短的关键词或更换表达方式。' : '输入关键词，寻找感兴趣的内容。';
        $emptyTitle = '没有找到相关内容';
        $emptyDesc = $keywords !== '' ? '试试更短的关键词，或者换一种表达方式。' : '输入一个更明确的关键词，再试一次。';
        $emptyActionText = $siteUrl !== '' ? '返回首页' : '';
        $emptyActionUrl = $siteUrl;
    } elseif ($widget->is('author')) {
        $label = '作者文章';
        $title = pf_text($widget->author->screenName ?? '');
        $listTitle = $title !== '' ? $title . ' 的文章' : '作者文章';
        $desc = '这里收录了该作者发布的全部内容。';
        $emptyTitle = '这位作者暂时还没有文章';
        $emptyDesc = '先看看站点里的其他内容，新的文章也许很快会出现。';
        $emptyActionText = $siteUrl !== '' ? '返回首页' : '';
        $emptyActionUrl = $siteUrl;
    } else {
        $title = pf_capture_output(static fn() => $widget->archiveTitle());
        $desc = '内容会随着时间继续沉淀在这里。';
        $emptyActionText = $siteUrl !== '' ? '返回首页' : '';
        $emptyActionUrl = $siteUrl;
    }

    try {
        $count = method_exists($widget, 'getTotal')
            ? max(0, (int) $widget->getTotal())
            : (isset($widget->total) ? max(0, (int) $widget->total) : 0);
    } catch (\Throwable $e) {
        pf_report('archive_total', $e);
        $count = 0;
    }

    return [
        'label' => $label,
        'title' => $title,
        'desc' => $desc,
        'listTitle' => $listTitle,
        'count' => $count,
        'emptyTitle' => $emptyTitle,
        'emptyDesc' => $emptyDesc,
        'emptyActionText' => $emptyActionText,
        'emptyActionUrl' => $emptyActionUrl,
    ];
}

function pf_add_pagination_titles(
    string $html,
    string $prevTitle = '上一页',
    string $nextTitle = '下一页',
    string $moreTitle = '加载更多文章'
): string {
    $html = preg_replace_callback(
        '/<li\b([^>]*)class=(["\'])([^"\']*\b(prev|next)\b[^"\']*)\2([^>]*)>\s*<a\b([^>]*)>/iu',
        static function (array $matches) use ($prevTitle, $nextTitle): string {
            $itemClass = strtolower((string) ($matches[3] ?? ''));
            $title = str_contains($itemClass, 'prev') ? $prevTitle : $nextTitle;
            $attrs = (string) ($matches[6] ?? '');
            if (!preg_match('/\btitle=/i', $attrs)) {
                $attrs .= ' title="' . pf_esc_attr($title) . '"';
            }
            if (!preg_match('/\baria-label=/i', $attrs)) {
                $attrs .= ' aria-label="' . pf_esc_attr($title) . '"';
            }

            return '<li' . $matches[1] . 'class=' . $matches[2] . $matches[3] . $matches[2] . $matches[5] . '><a' . $attrs . '>';
        },
        $html
    ) ?? $html;

    $html = preg_replace_callback(
        '/<a\b((?:(?!\btitle=)[^>])*)>(\s*加载更多\s*)<\/a>/iu',
        static function (array $matches) use ($moreTitle): string {
            $attrs = trim((string) ($matches[1] ?? ''));
            if (!preg_match('/\baria-label=/i', $attrs)) {
                $attrs .= ' aria-label="' . pf_esc_attr($moreTitle) . '"';
            }
            if (!preg_match('/\btitle=/i', $attrs)) {
                $attrs .= ' title="' . pf_esc_attr($moreTitle) . '"';
            }

            return '<a ' . trim($attrs) . '>' . $matches[2] . '</a>';
        },
        $html
    ) ?? $html;

    return $html;
}

function pf_render_pagination_html(
    callable $renderer,
    string $prevTitle = '上一页',
    string $nextTitle = '下一页',
    string $moreTitle = '加载更多文章'
): string {
    ob_start();
    $renderer();
    return pf_add_pagination_titles((string) ob_get_clean(), $prevTitle, $nextTitle, $moreTitle);
}

/**
 * 更健壮的音乐列表解析：支持 曲名||URL 格式避免 http:// 歧义
 * 同时兼容旧 冒号 格式
 *
 * @return list<array{name:string,url:string,artist:string}>
 */
function pf_parse_music_list(mixed $raw, bool $allowIds = false): array
{
    $lines  = pf_lines($raw);
    $result = [];
    foreach ($lines as $line) {
        $name = '';
        $url = '';
        $artist = '';
        if (str_contains($line, '||')) {
            $parts  = explode('||', $line, 3);
            $name   = trim($parts[0] ?? '');
            $rawUrl = trim($parts[1] ?? '');
            $artist = trim($parts[2] ?? '');
            $url    = pf_safe_url($rawUrl);
            if ($url === '' && $allowIds && preg_match('/^\d+$/', $rawUrl) === 1) {
                $url = $rawUrl;
            }
        } else {
            if ($allowIds && preg_match('/^\s*([^|:]+?)\s*:\s*(\d+)\s*(?::\s*(.+))?$/u', $line, $idMatch) === 1) {
                $name = trim((string) ($idMatch[1] ?? ''));
                $url = trim((string) ($idMatch[2] ?? ''));
                $artist = trim((string) ($idMatch[3] ?? ''));
            }

            if ($url === '') {
                if (preg_match('/^\s*(.+?)\s*:\s*(https?:\/\/\S+?)(?:\s+[|:：-]\s+(.+))?\s*$/u', $line, $legacyMatch) === 1) {
                    $name = trim((string) ($legacyMatch[1] ?? ''));
                    $url = pf_safe_url(trim((string) ($legacyMatch[2] ?? '')));
                    $artist = trim((string) ($legacyMatch[3] ?? ''));
                }
            }
        }
        if ($name === '' || $url === '') {
            continue;
        }
        if (count($result) >= PF_MAX_MUSIC) {
            break;
        }
        $result[] = ['name' => $name, 'url' => $url, 'artist' => $artist];
    }
    return $result;
}

/**
 * @return list<array{name:string,url:string,artist:string}>
 */
function pf_prepare_music_tracks(mixed $raw, bool $allowIds, object $options): array
{
    static $cache = [];
    $key = pf_text($raw) . "\n" . ($allowIds ? '1' : '0') . "\n" . strtolower(rtrim(pf_text($options->siteUrl ?? ''), '/'));
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $tracks = pf_parse_music_list($raw, $allowIds);
    $result = [];

    foreach ($tracks as $track) {
        $url = trim((string) ($track['url'] ?? ''));
        if ($url === '') {
            continue;
        }

        if (!preg_match('/^\d+$/', $url)) {
            $url = pf_frontend_url($url, $options);
            if ($url === '') {
                continue;
            }
        }

        $result[] = [
            'name' => trim((string) ($track['name'] ?? '')),
            'url' => $url,
            'artist' => trim((string) ($track['artist'] ?? '')),
        ];
    }

    return $cache[$key] = $result;
}

/**
 * @return array{focus:int,short:int,long:int,longAfter:int,autoStart:bool,musicEnable:bool}
 */
function pf_pomodoro_settings(object $options): array
{
    return [
        'focus'      => pf_int_in_range($options->pomodoroFocus ?? 25, 25, 5, 90),
        'short'      => pf_int_in_range($options->pomodoroShort ?? 5, 5, 1, 30),
        'long'       => pf_int_in_range($options->pomodoroLong ?? 15, 15, 5, 60),
        'longAfter'  => pf_int_in_range($options->pomodoroLongAfter ?? 4, 4, 2, 8),
        'autoStart'  => pf_bool($options->pomodoroAutoStart ?? 'false'),
        'musicEnable'=> pf_bool($options->musicEnable ?? 'false'),
    ];
}

/**
 * 获取特定社交平台的 SVG 图标
 */
function pf_get_social_icon(string $platform): string
{
    $platform = match (strtolower(trim($platform))) {
        'weixin' => 'wechat',
        'email' => 'mail',
        'x', 'x-twitter', 'twitter-x' => 'twitter',
        'tiktok' => 'douyin',
        'rednote' => 'xiaohongshu',
        'xhs' => 'xiaohongshu',
        default => strtolower(trim($platform)),
    };

    return match ($platform) {
        'github' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>',
        'gitee' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.984 0A12 12 0 000 12a12 12 0 0012 12 12 12 0 0012-12A12 12 0 0012 0a12 12 0 00-.016 0zm6.09 5.333c.328 0 .593.266.592.593v1.9c0 .328-.265.592-.593.592H8.058c-.99 0-1.824.793-1.824 1.78v3.468c0 .987.834 1.78 1.824 1.78h6.41v-1.9H8.163c-.328 0-.593-.265-.593-.593v-1.9c0-.327.265-.592.593-.592h9.855c.328 0 .592.265.592.592v4.735c0 1.98-1.626 3.585-3.633 3.585H7.01c-1.98 0-3.585-1.605-3.585-3.585V10.19c0-1.98 1.605-3.585 3.585-3.585h11.064z"/></svg>',
        'weibo' => '<svg viewBox="0 0 1025 1024" fill="currentColor"><path d="M690.325333 102.848c-13.802667 2.453333-44.629333 14.293333-44.885333 39.808-0.256 25.493333 27.050667 42.133333 40.832 43.413333 50.88 0 294.208-13.205333 249.706667 221.568-6.165333 25.749333-10.88 65.173333 19.669333 73.472 27.754667 6.976 44.885333-22.016 52.586667-44.096C1011.925333 411.328 1124.458667 74.858667 690.325333 102.848zM753.621333 495.786667c0 0-51.008 11.029333-26.88-26.922667 37.888-74.218667-23.786667-196.010667-183.658667-115.072-55.082667 29.354667-55.082667 8.554667-53.248-28.16 4.949333-200.469333-366.634667-57.536-471.914667 203.114667C-41.429333 686.912 53.632 823.552 200.682667 883.2c358.933333 128.128 620.266667-83.904 664.810667-220.949333C924.906667 456.32 753.621333 495.786667 753.621333 495.786667zM409.429333 835.797333c-169.898667 23.338667-320.490667-51.328-336.426667-166.677333-15.850667-115.413333 108.992-227.946667 278.890667-251.285333 169.898667-23.36 320.469333 51.242667 336.405333 166.656C704.170667 699.882667 579.285333 812.330667 409.429333 835.797333zM834.624 435.349333c17.088 4.266667 23.744-9.749333 25.621333-22.549333 1.749333-12.8 31.253333-186.325333-158.250667-166.314667-14.336 1.578667-24 10.154667-22.336 22.741333 1.578667 12.608 12.202667 19.669333 20.288 18.709333 8.085333-0.938667 134.656-23.125333 124.288 110.250667C826.133333 410.325333 817.6 431.082667 834.624 435.349333zM354.069333 498.624c-88.554667 16.981333-149.461333 87.744-135.978667 158.08 13.482667 70.336 96.256 113.536 184.853333 96.533333 88.576-16.96 149.418667-87.744 135.978667-158.037333C525.376 524.885333 442.666667 481.642667 354.069333 498.624z"/></svg>',
        'wechat' => '<svg viewBox="0 0 1024 1024" fill="currentColor"><path d="M308.73856 119.23456C23.65696 170.15296-71.37024 492.23936 155.392 639.66464c12.43392 7.99232 12.43392 7.104-6.21824 62.76096l-15.98464 47.65952 57.43104-30.784 57.43104-30.78656 30.49216 7.40096c31.96928 7.99232 72.82432 13.61664 100.0576 13.61664l16.28416 0-5.62688-21.61152c-44.70016-164.5952 109.82912-327.71072 310.8352-327.71072l27.2384 0-5.62432-19.53792C677.59616 186.43456 491.392 86.67136 308.73856 119.23456zM283.87072 263.40352c30.1952 20.4288 31.97184 64.5376 2.95936 83.48416-47.06816 30.78656-102.1312-23.38816-70.45632-69.57056C230.28736 256.59648 263.74144 249.78688 283.87072 263.40352zM526.62016 263.40352c49.73568 33.45408 12.43392 110.71744-43.22304 89.40288-40.25856-15.39328-44.99712-70.75072-7.40096-90.5856C490.79808 254.22848 513.88928 254.81984 526.62016 263.40352zM636.44928 385.37216c-141.2096 25.7536-239.19872 132.91776-233.57184 256.06656 7.40096 164.89472 200.71168 278.56896 386.32448 227.65312l21.90592-5.92128 46.1824 24.8704c25.4592 13.9136 46.77376 23.97696 47.36512 22.79168 0.59392-1.47968-4.43648-19.24352-10.95168-39.6672-14.79936-45.59104-15.09632-42.33472 4.73856-56.54272C1121.64864 654.464 925.67552 332.97408 636.44928 385.37216zM630.82496 518.28992c12.4288 8.28928 18.944 29.01248 13.61408 44.1088-11.24864 32.26624-59.49952 34.63424-72.52992 3.55328C557.10976 530.13248 597.9648 496.97536 630.82496 518.28992zM828.57472 521.84576c19.53792 18.64704 16.2816 50.32448-6.51264 62.16448-34.93376 17.76128-71.63904-17.76128-53.58336-51.80416C780.32128 510.2976 810.81344 504.97024 828.57472 521.84576z"/></svg>',
        'zhihu' => '<svg viewBox="0 0 1024 1024" fill="currentColor"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-512S759.4 64 512 64z m-90.7 477.8l-0.1 1.5c-1.5 20.4-6.3 43.9-12.9 67.6l24-18.1 71 80.7c9.2 33-3.3 63.1-3.3 63.1l-95.7-111.9v-0.1c-8.9 29-20.1 57.3-33.3 84.7-22.6 45.7-55.2 54.7-89.5 57.7-34.4 3-23.3-5.3-23.3-5.3 68-55.5 78-87.8 96.8-123.1 11.9-22.3 20.4-64.3 25.3-96.8H264.1s4.8-31.2 19.2-41.7h101.6c0.6-15.3-1.3-102.8-2-131.4h-49.4c-9.2 45-41 56.7-48.1 60.1-7 3.4-23.6 7.1-21.1 0 2.6-7.1 27-46.2 43.2-110.7 16.3-64.6 63.9-62 63.9-62-12.8 22.5-22.4 73.6-22.4 73.6h159.7c10.1 0 10.6 39 10.6 39h-90.8c-0.7 22.7-2.8 83.8-5 131.4H519s12.2 15.4 12.2 41.7H421.3z m346.5 167h-87.6l-69.5 46.6-16.4-46.6h-40.1V321.5h213.6v387.3z"/></svg>',
        'bilibili' => '<svg viewBox="0 0 1024 1024" fill="currentColor"><path d="M777.514667 131.669333a53.333333 53.333333 0 0 1 0 75.434667L728.746667 255.829333h49.92A160 160 0 0 1 938.666667 415.872v320a160 160 0 0 1-160 160H245.333333A160 160 0 0 1 85.333333 735.872v-320a160 160 0 0 1 160-160h49.749334L246.4 207.146667a53.333333 53.333333 0 1 1 75.392-75.434667l113.152 113.152c3.370667 3.370667 6.186667 7.04 8.448 10.965333h137.088c2.261333-3.925333 5.12-7.68 8.490667-11.008l113.109333-113.152a53.333333 53.333333 0 0 1 75.434667 0z m1.152 231.253334H245.333333a53.333333 53.333333 0 0 0-53.205333 49.365333l-0.128 4.010667v320c0 28.117333 21.76 51.157333 49.365333 53.162666l3.968 0.170667h533.333334a53.333333 53.333333 0 0 0 53.205333-49.365333l0.128-3.968v-320c0-29.44-23.893333-53.333333-53.333333-53.333334z m-426.666667 106.666666c29.44 0 53.333333 23.893333 53.333333 53.333334v53.333333a53.333333 53.333333 0 1 1-106.666666 0v-53.333333c0-29.44 23.893333-53.333333 53.333333-53.333334z m320 0c29.44 0 53.333333 23.893333 53.333333 53.333334v53.333333a53.333333 53.333333 0 1 1-106.666666 0v-53.333333c0-29.44 23.893333-53.333333 53.333333-53.333334z"/></svg>',
        'qq' => '<svg viewBox="0 0 32 32" fill="currentColor"><path d="M28.527 20.047c-.329-1.016-.683-2.025-1.068-3.02l-1.443-3.595c.005-.041.02-.744.02-1.115C26.036 6.177 23.136 0 16 0S5.964 6.177 5.964 12.323c0 .365.02 1.073.02 1.115l-1.443 3.593c-.385.996-.739 2-1.068 3.016-1.359 4.38-.921 6.193-.583 6.235.719.083 2.803-3.297 2.803-3.297 0 1.959 1.009 4.516 3.192 6.36-.812.249-1.817.64-2.459 1.115-.577.427-.505.859-.4 1.036.457.771 7.843.489 9.973.251 2.136.239 9.521.52 9.979-.251.104-.177.177-.609-.4-1.036-.647-.475-1.647-.865-2.464-1.115 2.183-1.849 3.192-4.407 3.192-6.365 0 0 2.084 3.385 2.803 3.297.339-.036.776-1.855-.583-6.229zM13.271 4c.969-.041 1.797 1.057 1.849 2.464.052 1.4-.693 2.572-1.656 2.615-.969.047-1.797-1.057-1.849-2.459-.052-1.407.688-2.579 1.656-2.62zm5.458 0c.968.041 1.708 1.213 1.656 2.62-.052 1.401-.88 2.505-1.849 2.459-.963-.043-1.708-1.215-1.656-2.615.052-1.407.88-2.505 1.849-2.464zm-8.833 7.057c.255-.573 2.859-1.208 6.088-1.208h.032c3.224 0 5.833.635 6.088 1.208.016.025.021.052.021.083 0 .043-.016.079-.037.111-.219.317-3.109 1.889-6.067 1.889h-.037c-2.963 0-5.853-1.572-6.072-1.889-.043-.053-.048-.131-.016-.193zm13.896 11.495c-.297 4.907-3.204 7.984-7.699 8.032h-.181c-4.496-.048-7.407-3.125-7.699-8.032-.109-1.797 0-3.323.193-4.573.427.089.853.167 1.281.235v4.677c0 0 2.208.448 4.421.136v-4.297c.651.036 1.281.052 1.875.041h.032c2.239.032 4.953-.271 7.577-.792.199 1.251.303 2.776.199 4.573z"/></svg>',
        'xiaohongshu' => '<svg viewBox="0 0 1024 1024" fill="currentColor"><path d="M960 797.248V226.784C960 137.248 886.752 64 797.216 64H226.784C137.248 64 64 137.248 64 226.784v570.464c0 88.64 71.808 161.344 160.16 162.752h575.68c88.32-1.408 160.16-74.08 160.16-162.752zM700.544 384h50.4v18.048c0 1.44 0.704 2.112 2.08 2.08 29.888-0.896 60 0.064 75.424 30.72 9.184 18.176 7.296 45.824 6.784 67.52-0.032 1.28 0.576 1.984 1.792 2.112 3.52 0.32 6.944 0.64 10.272 1.056 59.424 7.136 47.68 63.168 47.872 107.2 0.096 15.36-1.632 26.592-5.12 33.76-7.36 14.848-20.544 23.36-39.552 25.472H813.44l-18.944-43.968a1.44 1.44 0 0 1 0.096-1.344 1.376 1.376 0 0 1 1.152-0.64l40.192-0.032c2.24 0 4.352-0.96 5.888-2.624a8.896 8.896 0 0 0 2.368-6.176c-0.192-13.44-0.288-26.848-0.224-40.256 0-12.064-5.696-18.24-17.184-18.56-12.992-0.32-37.6-0.32-73.856 0.064-1.28 0-1.92 0.704-1.92 2.08l-0.192 111.456H700.48l-0.16-111.936a1.984 1.984 0 0 0-1.952-2.048h-47.04a2.24 2.24 0 0 1-2.176-2.24l0.064-48.704c0-1.632 0.768-2.464 2.304-2.464l46.496 0.096a2.208 2.208 0 0 0 1.6-0.704 2.432 2.432 0 0 0 0.64-1.664v-42.112a2.784 2.784 0 0 0-2.688-2.848l-28.704 0.128c-1.504 0-2.24-0.8-2.24-2.368l-0.096-48.96c0-1.44 0.64-2.144 2.08-2.144h29.728c1.28 0 1.92-0.64 1.92-2.016l0.32-17.984zM752.736 504.736l31.264-0.064c0.512 0 0.992-0.224 1.344-0.608a2.016 2.016 0 0 0 0.544-1.408l-0.16-39.136c0-3.072-2.24-5.568-4.96-5.568l-25.088 0.064a4.704 4.704 0 0 0-3.52 1.664 5.984 5.984 0 0 0-1.44 4l0.16 39.136c0 1.088 0.864 1.92 1.856 1.92zM429.344 508.256c-12.16 0.224-34.144 3.616-38.944-12.032-2.912-9.344 3.68-22.368 7.68-31.488 11.392-25.952 22.56-52 33.536-78.144 0.448-1.056 1.216-1.6 2.304-1.6h48.096c0.416 0 0.768 0.224 0.96 0.576a1.28 1.28 0 0 1 0.128 1.152l-27.84 65.056c-0.64 1.504-0.48 3.2 0.352 4.608a4.544 4.544 0 0 0 3.84 2.176h41.216c0.512 0 0.96 0.256 1.248 0.672 0.256 0.448 0.32 0.96 0.096 1.44-11.904 27.744-23.776 55.296-35.616 82.656-1.184 2.72-1.696 4.736-1.504 6.016 0.416 2.784 1.984 4.192 4.672 4.224l26.08 0.16c1.504 0.032 1.984 0.768 1.376 2.24l-16.864 39.68a3.328 3.328 0 0 1-3.2 2.208c-26.496 0.32-45.024 0.32-55.584-0.16-17.472-0.8-21.76-16.096-14.976-31.872l23.968-55.936a1.216 1.216 0 0 0-0.096-1.088 1.088 1.088 0 0 0-0.96-0.544zM229.504 671.968h-18.88l-18.496-43.424a1.408 1.408 0 0 1 0.096-1.312 1.28 1.28 0 0 1 1.088-0.64l26.112-0.064a6.112 6.112 0 0 0 5.984-6.24l0.704-230.304a2.24 2.24 0 0 1 2.208-2.304h44.928c2.112 0 3.168 1.12 3.2 3.328 0.192 77.984 0.192 154.624 0 229.952-0.128 30.912-14.464 52.032-46.944 51.008zM650.944 671.968h-170.432l22.848-51.52a3.04 3.04 0 0 1 2.976-1.952l41.728 0.064c1.472 0 2.24-0.736 2.24-2.24v-156.32c0-1.344-0.64-2.016-1.92-2.016l-27.68-0.032c-1.248 0-2.24-1.088-2.24-2.4v-50.144c0-0.768 0.576-1.408 1.312-1.408h112.832c1.408 0 2.08 0.736 2.08 2.208l0.064 49.6c0 1.44-0.704 2.176-2.112 2.176h-27.904c-1.28 0-1.92 0.672-1.92 2.016v156.224c0 1.504 0.736 2.24 2.144 2.24l44.224 0.096c1.216 0 1.824 0.64 1.824 1.92L650.944 672zM853.92 408.864c34.816-23.936 59.328 37.088 21.184 47.552-6.208 1.728-16.096 1.824-29.632 0.32-1.216-0.128-1.792-0.8-1.792-2.08-0.192-14.4-3.04-36.672 10.24-45.76zM373.312 588.288l-23.04 53.664c-2.08 4.8-4.352 4.896-6.88 0.384-16.96-30.656-22.72-55.68-26.048-93.792-2.56-29.6-4.768-59.2-6.688-88.864-0.064-1.344 0.544-2.016 1.824-2.016l46.688 0.032c1.312 0 2.048 0.704 2.144 2.048 2.4 34.496 4.928 68.896 7.552 103.2 0.672 8.832 2.176 16.16 4.48 21.984a4.16 4.16 0 0 1-0.032 3.36zM128 586.304v-2.208a22.592 22.592 0 0 0 4.16-10.112c3.456-38.08 6.272-76.128 8.48-114.208 0.096-1.184 0.672-1.792 1.792-1.792h47.68c0.416 0 0.832 0.192 1.152 0.544 0.288 0.32 0.448 0.768 0.416 1.216a6351.04 6351.04 0 0 1-8.416 105.184c-2.24 25.44-10.368 59.488-27.36 80.128-1.088 1.312-2.016 1.184-2.72-0.416L128 586.304zM453.184 671.968h-69.056l-8.8-3.488c-1.248-0.48-1.6-1.344-1.024-2.592l21.664-49.6c0.64-1.44 1.664-1.984 3.136-1.6 23.68 6.432 51.104 3.776 75.328 3.872 1.504 0.032 1.92 0.768 1.28 2.176l-22.528 51.2z"/></svg>',
        'telegram' => '<svg viewBox="0 0 16 16" fill="currentColor"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.287 5.906c-.778.324-2.334.994-4.666 2.01-.378.15-.577.298-.595.442-.03.243.275.339.69.47l.175.055c.408.133.958.288 1.243.294.26.006.549-.1.868-.32 2.179-1.471 3.304-2.214 3.374-2.23.05-.012.12-.026.166.016.047.041.042.12.037.141-.03.129-1.227 1.241-1.846 1.817-.193.18-.33.307-.358.336a8.154 8.154 0 0 1-.188.186c-.38.366-.664.64.015 1.088.327.216.589.393.85.571.284.194.568.387.936.629.093.06.183.125.27.187.331.236.63.448.997.414.214-.02.435-.22.547-.82.265-1.417.786-4.486.906-5.751a1.426 1.426 0 0 0-.013-.315.337.337 0 0 0-.114-.217.526.526 0 0 0-.31-.093c-.3.005-.763.166-2.984 1.09z"/></svg>',
        'douyin' => '<svg viewBox="0 0 48 48" fill="currentColor"><path d="M21.3583 19.14C15.4696 18.8559 11.3764 20.9554 9.07862 25.4385C5.63194 32.1631 8.48051 43.1666 19.9791 43.1666C31.4776 43.1666 31.8103 32.0555 31.8103 30.8915C31.8103 30.1154 31.8103 25.7765 31.8103 17.8746C34.2697 19.4324 36.3433 20.3701 38.031 20.6877C39.7188 21.0054 40.7918 21.1462 41.2499 21.1101V14.6343C39.6888 14.4461 38.3388 14.0874 37.1999 13.5582C35.4915 12.7643 32.104 10.5612 32.104 7.33214C32.1063 7.34793 32.1063 6.51499 32.104 4.83331H24.9859C24.9648 20.6494 24.9648 29.3354 24.9859 30.8915C25.0177 33.2255 23.207 36.4905 19.5358 36.4905C15.8645 36.4905 14.0538 33.2282 14.0538 31.1239C14.0538 29.8357 14.4962 27.9685 16.3253 26.5858C17.41 25.7659 18.9155 25.4385 21.3583 25.4385C21.3583 24.6829 21.3583 22.5834 21.3583 19.14Z"/></svg>',
        'twitter' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'facebook' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'mail' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>',
        default => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>',
    };
}

/**
 * 解析社交媒体链接配置
 * 
 * @param mixed $raw 原始多行配置
 * @return list<array{platform:string,url:string,icon:string}>
 */
function pf_parse_social_links(mixed $raw): array
{
    static $cache = [];
    $key = pf_text($raw);
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $lines = pf_lines($raw);
    $result = [];

    foreach ($lines as $line) {
        $parts = explode('||', $line, 2);
        if (count($parts) < 2) continue;

        $platform = strtolower(trim($parts[0]));
        $safeUrl = pf_normalize_social_url($parts[1]);

        if ($platform === '' || $safeUrl === '') continue;

        $result[] = [
            'platform' => $platform,
            'url'      => $safeUrl,
            'icon'     => pf_get_social_icon($platform),
        ];
    }

    return $cache[$key] = $result;
}

/**
 * 解析友情链接配置
 * 
 * @param mixed $raw 原始多行配置
 * @return list<array{name:string,url:string,desc:string,avatar:string}>
 */
function pf_parse_friend_links(mixed $raw): array
{
    static $cache = [];
    $lines = pf_lines($raw);
    $result = [];
    $options = \Typecho\Widget::widget('\Widget\Options');
    $fallbackAvatar = pf_default_avatar_url($options);
    $key = pf_text($raw) . "\n" . $fallbackAvatar;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    foreach ($lines as $line) {
        $parts = explode('||', $line, 4);
        if (count($parts) < 2) continue;

        $name = trim($parts[0]);
        $url = pf_normalize_social_url($parts[1]);

        if ($name === '' || $url === '' || str_starts_with($url, 'mailto:')) continue;

        $desc = isset($parts[2]) ? trim($parts[2]) : '';
        $avatar = isset($parts[3]) ? pf_safe_url(trim($parts[3])) : '';

        if ($avatar === '') {
            $avatar = $fallbackAvatar;
        }

        $result[] = [
            'name'   => $name,
            'url'    => $url,
            'desc'   => $desc,
            'avatar' => $avatar,
        ];
    }

    return $cache[$key] = $result;
}
