<?php
declare(strict_types=1);

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$turnPageType = pf_text($this->options->turnPageType ?? 'page');
?>
<?php if ($turnPageType === 'more'): ?>
<div class="pf-load-more">
    <?php
    echo pf_render_pagination_html(
        function (): void {
            $this->pageLink('加载更多', 'next');
        },
        '上一页',
        '下一页',
        '加载更多文章'
    );
    ?>
</div>
<?php else: ?>
<nav class="pf-pagination" aria-label="文章分页">
    <?php
    echo pf_render_pagination_html(
        function (): void {
            $this->pageNav(
                '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round"/></svg>',
                '<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round"/></svg>',
                1,
                '…',
                [
                    'wrapTag'      => 'ul',
                    'wrapClass'    => 'pagination-container',
                    'itemTag'      => 'li',
                    'textTag'      => 'a',
                    'currentClass' => 'active',
                    'prevClass'    => 'prev',
                    'nextClass'    => 'next',
                ]
            );
        }
    );
    ?>
</nav>
<?php endif; ?>
