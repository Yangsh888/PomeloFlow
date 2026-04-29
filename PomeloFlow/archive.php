<?php
declare(strict_types=1);

if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$archive = pf_archive_context($this);
?>
<?php $this->need('public/header.php'); ?>

<div class="pf-container">
    <div class="pf-archive-header pf-animate-up">
        <div class="pf-archive-label"><?php echo pf_esc_html($archive['label']); ?></div>
        <div class="pf-archive-title"><?php echo pf_esc_html($archive['title']); ?></div>
        <?php if ($archive['desc'] !== ''): ?>
        <div class="pf-archive-desc pf-animate-up">
            <?php echo pf_esc_html($archive['desc']); ?>
        </div>
        <?php endif; ?>
        <?php if ($archive['count'] > 0): ?>
        <div class="pf-archive-count pf-animate-up">共 <?php echo $archive['count']; ?> 篇</div>
        <?php endif; ?>
    </div>

    <?php $pfArchiveContext = $archive; ?>
    <?php $this->need('component/post-list.php'); ?>
    <?php $this->need('component/pagination.php'); ?>
</div>

<?php $this->need('public/footer.php'); ?>
