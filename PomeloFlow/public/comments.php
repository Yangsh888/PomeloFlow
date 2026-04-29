<?php
declare(strict_types=1);

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

$this->comments()->to($comments);
$security = $this->widget('\Widget\Security');
$commentCount = max(0, (int) ($this->commentsNum ?? 0));

if (!function_exists('pf_render_comment')) {
    function pf_render_comment(object $comments, object $options): void
    {
        $commentClass = ($comments->authorId && $comments->authorId == $comments->ownerId) ? ' by-owner' : '';

        $authorName   = pf_text($comments->author);
        $commentUrl   = pf_safe_url(pf_text($comments->url ?? ''));
        $isWaiting    = pf_text($comments->status) === 'waiting';
        $avatarUrl    = pf_avatar_url($comments->mail);
        ?>
        <div id="li-<?php $comments->theId(); ?>" class="pf-comment-item" role="listitem">
            <div id="<?php $comments->theId(); ?>" class="pf-comment-head">
                <img
                    class="pf-comment-avatar"
                    src="<?php echo pf_esc_attr($avatarUrl); ?>"
                    alt="<?php echo pf_esc_attr($authorName); ?>"
                    loading="lazy"
                >
                <div class="pf-comment-main">
                    <div class="pf-comment-meta">
                        <span class="pf-comment-author<?php echo pf_esc_attr($commentClass); ?>">
                            <?php if ($commentUrl !== ''): ?>
                                <a href="<?php echo pf_esc_attr($commentUrl); ?>" target="_blank" rel="nofollow noopener noreferrer">
                                    <?php echo pf_esc_html($authorName); ?>
                                </a>
                            <?php else: ?>
                                <?php echo pf_esc_html($authorName); ?>
                            <?php endif; ?>
                        </span>
                        <?php if ($isWaiting): ?>
                            <span class="pf-comment-waiting">（审核中）</span>
                        <?php endif; ?>
                        <time class="pf-comment-time"><?php echo pf_format_date((int) $comments->created); ?></time>
                        <button
                            class="pf-comment-reply-btn"
                            type="button"
                            data-comment-id="<?php $comments->theId(); ?>"
                            data-coid="<?php $comments->coid(); ?>"
                            aria-label="回复 <?php echo pf_esc_attr($authorName); ?>"
                        >回复</button>
                    </div>
                    <div class="pf-comment-body">
                        <?php echo pf_reply_prefix((int) ($comments->cid ?? 0), (int) $comments->parent, pf_text($comments->content)); ?>
                    </div>
                </div>
            </div>
            <?php if (!empty($comments->children)): ?>
            <div class="pf-comment-children">
                <?php $comments->threadedComments($options); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('threadedComments')) {
    function threadedComments(object $comments, object $options): void
    {
        pf_render_comment($comments, $options);
    }
}
?>
<section class="pf-comments" id="pf-comments" aria-describedby="pf-comments-help">
    <p class="sr-only" id="pf-comments-help">支持使用 Tab 键浏览评论操作；进入回复状态后，按 Esc 可以取消回复并返回原位置。</p>
    <h2 class="pf-comments-title">
        <?php if ($commentCount > 0): ?>
            <span class="pf-comments-count-num"><?php echo $commentCount; ?></span>
            条评论
        <?php else: ?>
            评论
        <?php endif; ?>
    </h2>

    <?php if ($this->allow('comment')): ?>
    <div id="pf-respond" class="pf-comment-form" data-respond-id="pf-respond">
        <div class="pf-comment-form-title">
            <span>
                <?php if ($this->user->hasLogin()): ?>
                    以
                    <a href="<?php $this->options->profileUrl(); ?>" data-no-instant><?php $this->user->screenName(); ?></a>
                    的身份发言 &bull;
                    <a href="<?php $this->options->logoutUrl(); ?>" data-no-instant>退出登录</a>
                <?php else: ?>
                    留下你的想法
                <?php endif; ?>
            </span>
            <span id="pf-cancel-reply" class="pf-comment-cancel-wrap" hidden>
                <span class="pf-comment-replying" id="pf-comment-replying" aria-live="polite"></span>
                <button type="button" class="pf-comment-cancel" id="pf-comment-cancel-btn">取消回复</button>
            </span>
        </div>

        <form
            method="post"
            action="<?php $this->commentUrl(); ?>"
            id="pf-comment-form"
        >
            <div class="pf-comment-field">
                <label class="pf-comment-label" for="pf-comment-text">评论内容</label>
                <textarea
                    id="pf-comment-text"
                    name="text"
                    placeholder="请输入评论内容"
                    required
                    aria-label="评论内容"
                ><?php $this->remember('text', false); ?></textarea>
            </div>

            <?php if (!$this->user->hasLogin()): ?>
            <div class="pf-comment-form-fields">
                <div class="pf-comment-field">
                    <label class="pf-comment-label" for="pf-comment-author">昵称 *</label>
                    <input
                        id="pf-comment-author"
                        type="text"
                        name="author"
                        placeholder="请输入昵称"
                        maxlength="150"
                        value="<?php $this->remember('author'); ?>"
                        required
                        autocomplete="name"
                        aria-label="昵称"
                    >
                </div>
                <div class="pf-comment-field">
                    <label class="pf-comment-label" for="pf-comment-mail">邮箱<?php echo $this->options->commentsRequireMail ? ' *' : ''; ?></label>
                    <input
                        id="pf-comment-mail"
                        type="email"
                        name="mail"
                        placeholder="用于接收回复提醒"
                        value="<?php $this->remember('mail'); ?>"
                        <?php if ($this->options->commentsRequireMail): ?>required<?php endif; ?>
                        autocomplete="email"
                        aria-label="邮箱"
                    >
                </div>
                <div class="pf-comment-field">
                    <label class="pf-comment-label" for="pf-comment-url">网站</label>
                    <input
                        id="pf-comment-url"
                        type="url"
                        name="url"
                        placeholder="可选，填写你的个人主页"
                        value="<?php $this->remember('url'); ?>"
                        <?php if (pf_comment_require_url($this->options)): ?>required<?php endif; ?>
                        inputmode="url"
                        autocomplete="url"
                        aria-label="网站"
                    >
                </div>
            </div>
            <?php endif; ?>

            <input type="hidden" name="parent" value="0">
            <input type="hidden" name="_" value="<?php echo pf_esc_attr($security->getToken($this->request->getRequestUrl())); ?>">

            <button
                type="submit"
                class="pf-comment-submit"
                id="pf-comment-submit"
            >提交评论</button>
            <div class="pf-comment-status" id="pf-comment-status" aria-live="polite" aria-atomic="true"></div>
        </form>
    </div>
    <?php else: ?>
    <div class="pf-empty pf-empty-comments">
        <div class="pf-empty-title">评论已关闭</div>
        <div class="pf-empty-desc">当前文章暂不开放评论交流。</div>
    </div>
    <?php endif; ?>

    <?php if ($comments->have()): ?>
    <div class="pf-comment-list" role="list">
        <?php $comments->listComments([
            'before'        => '',
            'after'         => '',
            'replyWord'     => '回复'
        ]); ?>
    </div>

    <?php
    $commentPagination = pf_render_pagination_html(
        function () use ($comments): void {
            $comments->pageNav(
                '<svg viewBox="0 0 24 24" width="14" height="14"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round"/></svg>',
                '<svg viewBox="0 0 24 24" width="14" height="14"><path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.75" fill="none" stroke-linecap="round"/></svg>',
                1, '…',
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
        },
        '上一页评论',
        '下一页评论'
    );
    ?>
    <?php if ($commentPagination !== ''): ?>
    <nav class="pf-comment-pagination" aria-label="评论分页">
        <?php echo $commentPagination; ?>
    </nav>
    <?php endif; ?>
    <?php elseif ($this->allow('comment')): ?>
    <div class="pf-empty pf-empty-comments">
        <div class="pf-empty-title">还没有评论</div>
        <div class="pf-empty-desc">期待你的第一条评论。</div>
    </div>
    <?php endif; ?>
</section>
