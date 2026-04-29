<?php
declare(strict_types=1);

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$siteUrl = pf_frontend_url(pf_text($this->options->siteUrl ?? ''), $this->options);
?>
<?php $this->need('public/header.php'); ?>

<div class="pf-404">
    <div class="pf-404-code" aria-hidden="true">404</div>
    <h1 class="pf-404-title">页面不存在</h1>
    <p class="pf-404-desc">
        您访问的内容可能已移动、删除，或暂时无法访问。<br>
        请返回首页继续浏览其他内容。
    </p>
    <a class="pf-404-home" href="<?php echo pf_esc_attr($siteUrl); ?>">
        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
            <path d="M3 12L12 3l9 9" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round"/>
            <path d="M9 21V12h6v9" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M5 21h14" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round"/>
        </svg>
        回到首页
    </a>
</div>

<?php $this->need('public/footer.php'); ?>
