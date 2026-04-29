<?php
declare(strict_types=1);

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$opts        = $this->options;
$themeUrl    = rtrim(pf_text($opts->themeUrl ?? ''), '/');
$safeSiteUrl = pf_frontend_url(pf_text($opts->siteUrl ?? ''), $opts);
$siteTitle   = pf_text($opts->title ?? '');
$recordNum   = trim(pf_text($opts->recordNum ?? ''));
$feedUrl     = pf_safe_url(pf_text($opts->feedUrl ?? ''));
$year        = date('Y');

$socialLinks        = pf_parse_social_links($opts->socialLinks ?? '');
$friendGlobalEnable = pf_bool($opts->friendGlobalEnable ?? 'false');
$friendGlobalLimit  = pf_int($opts->friendGlobalLimit ?? 10);
$friendLinks        = pf_parse_friend_links($opts->friendLinks ?? '');
if ($friendGlobalLimit > 0 && count($friendLinks) > $friendGlobalLimit) {
    $friendLinks = array_slice($friendLinks, 0, $friendGlobalLimit);
}
?>
</main>

<footer class="pf-footer">
    <div class="pf-footer-inner">
        <?php if (!empty($socialLinks) || ($friendGlobalEnable && !empty($friendLinks))): ?>
        <div class="pf-footer-top">
            <?php if (!empty($socialLinks)): ?>
            <div class="pf-footer-social">
                <?php foreach ($socialLinks as $social): ?>
                    <a class="pf-social-link" href="<?php echo pf_esc_attr($social['url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo pf_esc_attr($social['platform']); ?>" title="<?php echo pf_esc_attr($social['platform']); ?>">
                        <?php echo $social['icon']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($friendGlobalEnable && !empty($friendLinks)): ?>
            <div class="pf-footer-friends">
                <span class="pf-footer-friends-label">友情链接：</span>
                <div class="pf-footer-friends-links">
                    <?php foreach ($friendLinks as $friend): ?>
                        <a href="<?php echo pf_esc_attr($friend['url']); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo pf_esc_attr($friend['desc']); ?>">
                            <?php echo pf_esc_html($friend['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="pf-footer-meta">
            <div class="pf-footer-copy">
                Copyright &copy; <?php echo pf_esc_html($year); ?>
                <a href="<?php echo pf_esc_attr($safeSiteUrl); ?>"><?php echo pf_esc_html($siteTitle); ?></a>
                <?php if ($recordNum !== ''): ?>
                    <span class="pf-meta-sep" aria-hidden="true"></span>
                    <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer"><?php echo pf_esc_html($recordNum); ?></a>
                <?php endif; ?>
            </div>
            <div class="pf-footer-links">
                <a href="<?php echo pf_esc_attr($feedUrl !== '' ? $feedUrl : rtrim($safeSiteUrl, '/') . '/feed/'); ?>" rel="noopener noreferrer">RSS</a>
                <a href="https://github.com/Yangsh888/TypeRenew" target="_blank" rel="noopener noreferrer">TypeRenew</a>
                <a href="https://github.com/Yangsh888/PomeloFlow" target="_blank" rel="noopener noreferrer">PomeloFlow</a>
            </div>
        </div>
    </div>
</footer>

<button class="pf-backtop" id="pf-backtop" type="button" aria-label="返回顶部" aria-hidden="true" tabindex="-1">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>

<script type="module" src="<?php echo pf_esc_attr($themeUrl . '/assets/js/bootstrap/index.js'); ?>"></script>
<?php $this->footer(); ?>
</body>
</html>
