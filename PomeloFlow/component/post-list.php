<?php
declare(strict_types=1);

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$opts = $this->options;
$archive = (isset($pfArchiveContext) && is_array($pfArchiveContext)) ? $pfArchiveContext : pf_archive_context($this);
?>
<div class="pf-list-header">
    <span class="pf-list-title">
        <?php echo pf_esc_html($archive['listTitle']); ?>
    </span>
</div>

<?php if ($this->have()): ?>
<div class="pf-list" id="pf-post-list">
    <?php while ($this->next()): ?>
        <?php
        $permalink  = pf_safe_url(pf_text($this->permalink));
        $title      = pf_text($this->title);
        $thumb      = pf_thumb($this, $opts);
        $excerpt    = pf_excerpt($this);
        $createdTs  = (int) $this->created;
        $date       = date('Y-m-d', $createdTs);
        $dateText   = pf_format_date($createdTs);
        $cardClass  = $thumb === '' ? 'pf-card pf-animate-up pf-card-no-thumb' : 'pf-card pf-animate-up';
        ?>
        <a class="<?php echo pf_esc_attr($cardClass); ?>" href="<?php echo pf_esc_attr($permalink); ?>" title="<?php echo pf_esc_attr($title); ?>">
            <div class="pf-card-thumb">
                <?php if ($thumb !== ''): ?>
                    <img
                        src="<?php echo pf_esc_attr($thumb); ?>"
                        alt="<?php echo pf_esc_attr($title); ?>"
                        loading="lazy"
                        decoding="async"
                    >
                <?php else: ?>
                    <div class="pf-card-thumb-placeholder">
                        <span><?php echo pf_esc_html($title); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pf-card-body">
                <div class="pf-card-meta">
                    <?php
                    $categories = is_array($this->categories ?? null) ? $this->categories : [];
                    $catText = trim(pf_text($categories[0]['name'] ?? ''));
                    if ($catText === '') {
                        ob_start();
                        $this->category();
                        $catText = trim(strip_tags((string) ob_get_clean()));
                    }
                    if ($catText !== ''):
                    ?>
                        <span class="pf-card-cat"><?php echo pf_esc_html($catText); ?></span>
                    <?php endif; ?>
                    <time class="pf-card-date" datetime="<?php echo pf_esc_attr(pf_text($date)); ?>">
                        <span class="pf-card-date-text"><?php echo pf_esc_html(pf_text($dateText)); ?></span>
                    </time>
                </div>

                <div class="pf-card-title"><?php echo pf_esc_html($title); ?></div>

                <?php if ($excerpt !== ''): ?>
                    <div class="pf-card-excerpt"><?php echo $excerpt; ?></div>
                <?php endif; ?>
            </div>
        </a>
    <?php endwhile; ?>
</div>
<?php else: ?>
<div class="pf-empty pf-empty-posts">
    <div class="pf-empty-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24">
            <path d="M12 20a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"/>
            <path d="M12 8c.4-1.5 1.55-2.83 3-3.5"/>
            <path d="M12 8c-.4-1.5-1.55-2.83-3-3.5"/>
        </svg>
    </div>
    <div class="pf-empty-title"><?php echo pf_esc_html($archive['emptyTitle']); ?></div>
    <div class="pf-empty-desc"><?php echo pf_esc_html($archive['emptyDesc']); ?></div>
    <?php if ($archive['emptyActionText'] !== '' && $archive['emptyActionUrl'] !== ''): ?>
    <div class="pf-empty-actions">
        <a class="pf-empty-action" href="<?php echo pf_esc_attr($archive['emptyActionUrl']); ?>"><?php echo pf_esc_html($archive['emptyActionText']); ?></a>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
