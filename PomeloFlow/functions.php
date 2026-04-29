<?php
declare(strict_types=1);

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

require_once __DIR__ . '/libs/core.php';

function themeConfig(\Typecho\Widget\Helper\Form $form): void
{
    $validateUrlLines = static function ($value): bool {
        $lines = pf_lines($value);
        if (count($lines) > PF_MAX_MUSIC) {
            return false;
        }

        foreach ($lines as $line) {
            if (pf_safe_url($line) === '') {
                return false;
            }
        }

        return true;
    };

    $validateMusicList = static function ($value): bool {
        $lines = pf_lines($value);
        if (count($lines) > PF_MAX_MUSIC) {
            return false;
        }

        $request = \Typecho\Request::getInstance();
        $requestApi = pf_safe_url(pf_text($request->get('neteaseApi', '', $hasRequestApi)));
        if ($hasRequestApi) {
            $allowIds = $requestApi !== '';
        } else {
            $allowIds = pf_safe_url(pf_text(\Typecho\Widget::widget('\Widget\Options')->neteaseApi ?? '')) !== '';
        }

        return count(pf_parse_music_list($value, $allowIds)) === count($lines);
    };

    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl', null, null,
        _t('站点 Logo 地址'),
        _t('填写图片 URL，将替换顶部导航栏的站点名文字显示为 Logo 图片')
    );
    $logoUrl->addRule('maxLength', _t('Logo 地址长度不能超过 2048 个字符'), PF_MAX_URL_LEN);
    $logoUrl->addRule(static fn($value): bool => pf_validate_optional_url($value), _t('请填写合法的 Logo 地址'));
    $form->addInput($logoUrl);

    $faviconUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'faviconUrl', null, null,
        _t('Favicon 地址'),
        _t('自定义网页标签页图标 URL，留空则使用站点根目录的 favicon.ico')
    );
    $faviconUrl->addRule('maxLength', _t('Favicon 地址长度不能超过 2048 个字符'), PF_MAX_URL_LEN);
    $faviconUrl->addRule(static fn($value): bool => pf_validate_optional_url($value), _t('请填写合法的 Favicon 地址'));
    $form->addInput($faviconUrl);

    $selfIntro = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'selfIntro', null, null,
        _t('一句话自我介绍'),
        _t('展示在首页文章列表上方的欢迎区域，以及文章页「关于作者」卡片的全站统一介绍文案。支持换行（自动转为 &lt;br&gt;）')
    );
    $form->addInput($selfIntro);

    $recordNum = new \Typecho\Widget\Helper\Form\Element\Text(
        'recordNum', null, null,
        _t('备案号'),
        _t('ICP 备案号，将显示在页脚，留空则不显示')
    );
    $form->addInput($recordNum);

    $colorSchema = new \Typecho\Widget\Helper\Form\Element\Select(
        'colorSchema',
        ['auto' => '跟随系统', 'light' => '浅色模式', 'dark' => '深色模式'],
        'auto',
        _t('外观风格'),
        _t('「跟随系统」根据访客操作系统深浅色偏好自动切换，也可固定为浅色或深色')
    );
    $form->addInput($colorSchema);

    $turnPageType = new \Typecho\Widget\Helper\Form\Element\Radio(
        'turnPageType',
        ['page' => '数字翻页', 'more' => '加载更多'],
        'page',
        _t('翻页模式'),
        _t('首页文章列表的翻页方式')
    );
    $form->addInput($turnPageType);

    $indexThumbs = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'indexThumbs', null, null,
        _t('文章默认封面图库'),
        _t('每行填写一张图片 URL。当文章未设置封面图且正文中无图片时，随机取一张作为封面。留空则仅显示文字摘要')
    );
    $indexThumbs->addRule($validateUrlLines, _t('默认封面图库中的每一行都必须是合法的图片地址，且数量不能超过 %d 项', PF_MAX_MUSIC));
    $form->addInput($indexThumbs);

    $showCopyright = new \Typecho\Widget\Helper\Form\Element\Radio(
        'showCopyright',
        ['true' => '显示', 'false' => '不显示'],
        'true',
        _t('版权声明'),
        _t('在文章底部显示 CC 版权声明')
    );
    $form->addInput($showCopyright);

    $showAuthorCard = new \Typecho\Widget\Helper\Form\Element\Radio(
        'showAuthorCard',
        ['true' => '显示', 'false' => '不显示'],
        'true',
        _t('作者介绍卡片'),
        _t('在文章底部显示「关于作者」卡片')
    );
    $form->addInput($showAuthorCard);

    $pomodoroEnable = new \Typecho\Widget\Helper\Form\Element\Radio(
        'pomodoroEnable',
        ['true' => '启用', 'false' => '关闭'],
        'true',
        _t('番茄钟功能'),
        _t('关闭后首页将不显示番茄钟区域，仅展示文章列表')
    );
    $form->addInput($pomodoroEnable);

    $pomodoroFocus = new \Typecho\Widget\Helper\Form\Element\Text(
        'pomodoroFocus', null, '25',
        _t('专注时长（分钟）'),
        _t('范围 5–90，默认 25 分钟')
    );
    $pomodoroFocus->addRule('isInteger', _t('请填写整数'));
    $pomodoroFocus->addRule(static fn($value): bool => pf_validate_int_range($value, 5, 90), _t('专注时长需在 5 到 90 分钟之间'));
    $form->addInput($pomodoroFocus);

    $pomodoroShort = new \Typecho\Widget\Helper\Form\Element\Text(
        'pomodoroShort', null, '5',
        _t('短休时长（分钟）'),
        _t('范围 1–30，默认 5 分钟')
    );
    $pomodoroShort->addRule('isInteger', _t('请填写整数'));
    $pomodoroShort->addRule(static fn($value): bool => pf_validate_int_range($value, 1, 30), _t('短休时长需在 1 到 30 分钟之间'));
    $form->addInput($pomodoroShort);

    $pomodoroLong = new \Typecho\Widget\Helper\Form\Element\Text(
        'pomodoroLong', null, '15',
        _t('长休时长（分钟）'),
        _t('范围 5–60，默认 15 分钟')
    );
    $pomodoroLong->addRule('isInteger', _t('请填写整数'));
    $pomodoroLong->addRule(static fn($value): bool => pf_validate_int_range($value, 5, 60), _t('长休时长需在 5 到 60 分钟之间'));
    $form->addInput($pomodoroLong);

    $pomodoroLongAfter = new \Typecho\Widget\Helper\Form\Element\Text(
        'pomodoroLongAfter', null, '4',
        _t('每几个番茄触发长休'),
        _t('范围 2–8，默认 4 个')
    );
    $pomodoroLongAfter->addRule('isInteger', _t('请填写整数'));
    $pomodoroLongAfter->addRule(static fn($value): bool => pf_validate_int_range($value, 2, 8), _t('长休触发间隔需在 2 到 8 之间'));
    $form->addInput($pomodoroLongAfter);

    $pomodoroAutoStart = new \Typecho\Widget\Helper\Form\Element\Radio(
        'pomodoroAutoStart',
        ['false' => '手动开始（默认）', 'true' => '自动开始下一阶段'],
        'false',
        _t('自动开始'),
        _t('完成一个番茄/休息后，是否自动开始下一阶段')
    );
    $form->addInput($pomodoroAutoStart);

    $musicEnable = new \Typecho\Widget\Helper\Form\Element\Radio(
        'musicEnable',
        ['false' => '关闭（默认）', 'true' => '启用'],
        'false',
        _t('背景音乐播放器'),
        _t('启用后番茄钟区域会显示迷你音乐播放器')
    );
    $form->addInput($musicEnable);

    $tipMode = new \Typecho\Widget\Helper\Form\Element\Select(
        'tipMode',
        ['local' => '本地优先（推荐）', 'api' => 'API 优先'],
        'local',
        _t('专注提示加载策略'),
        _t('本地优先会先稳定显示内置提示，并在后续自动刷新时优先尝试一言 API；API 优先会在首次显示时先尝试远程获取，失败时回退到本地提示')
    );
    $form->addInput($tipMode);

    $musicList = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'musicList', null, null,
        _t('音乐列表'),
        _t('每行一首，格式：曲名||音频URL  或  曲名||音频URL||作者名。支持直链 MP3/OGG 等 HTML5 音频格式；若已配置下方网易云 API，也支持填写歌曲 ID（纯数字）')
    );
    $musicList->addRule($validateMusicList, _t('请按说明填写音乐列表，每行一首，且数量不能超过 %d 首', PF_MAX_MUSIC));
    $form->addInput($musicList);

    $neteaseApi = new \Typecho\Widget\Helper\Form\Element\Text(
        'neteaseApi', null, null,
        _t('网易云音乐 API 地址（可选）'),
        _t('填写自部署的 NeteaseCloudMusicApi 地址，如 https://api.example.com，启用后音乐列表中可填写网易云歌曲 ID（纯数字）自动解析播放链接')
    );
    $neteaseApi->addRule(static fn($value): bool => pf_validate_optional_url($value), _t('请填写合法的网易云音乐 API 地址'));
    $form->addInput($neteaseApi);

    $socialLinks = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'socialLinks', null, null,
        _t('社交媒体链接'),
        _t('每行填写一个，格式：平台标识||链接。支持的平台标识有：github, gitee, weibo, wechat/weixin, zhihu, bilibili, qq, xiaohongshu/xhs/rednote, telegram, douyin/tiktok, twitter/x, facebook, mail/email 等。留空则不显示。将展示在页脚及文章底部的作者卡片中。')
    );
    $socialLinks->addRule(static fn($value): bool => pf_validate_social_links($value), _t('请按“平台标识||链接”填写社交媒体链接，邮箱仅支持合法邮箱地址或 mailto: 地址'));
    $form->addInput($socialLinks);

    $friendGlobalEnable = new \Typecho\Widget\Helper\Form\Element\Radio(
        'friendGlobalEnable',
        ['true' => '开启', 'false' => '关闭'],
        'false',
        _t('全局页脚友链'),
        _t('是否在全站底部显示极简纯文本形式的友情链接')
    );
    $form->addInput($friendGlobalEnable);

    $friendGlobalLimit = new \Typecho\Widget\Helper\Form\Element\Text(
        'friendGlobalLimit', null, '10',
        _t('全局友链显示数量'),
        _t('最多在底部显示几个友链')
    );
    $friendGlobalLimit->addRule('isInteger', _t('请填写整数'));
    $friendGlobalLimit->addRule(static fn($value): bool => pf_validate_int_range($value, 0, 100), _t('数量需在 0 到 100 之间'));
    $form->addInput($friendGlobalLimit);

    $friendLinks = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'friendLinks', null, null,
        _t('友情链接数据池'),
        _t('每行填写一个，格式：名称||网址||简介||头像URL。用于独立友链页面（卡片）和底部全局友链（纯文字）。')
    );
    $friendLinks->addRule(static fn($value): bool => pf_validate_friend_links($value), _t('请按“名称||网址||简介||头像URL”填写友情链接，网址需为合法 HTTP/HTTPS 地址，头像可留空'));
    $form->addInput($friendLinks);
}

function themeFields(\Typecho\Widget\Helper\Layout $layout): void
{
    $articleDesc = new \Typecho\Widget\Helper\Form\Element\Textarea(
        'articleDesc', null, null,
        _t('文章摘要'),
        _t('展示于文章列表，留空则自动截取正文前 ' . PF_EXCERPT_LEN . ' 字')
    );
    $layout->addItem($articleDesc);

    $bannerUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'bannerUrl', null, null,
        _t('文章封面图'),
        _t('填写图片 URL，用于文章列表封面和文章顶部 Banner')
    );
    $bannerUrl->addRule('maxLength', _t('文章封面图地址长度不能超过 2048 个字符'), PF_MAX_URL_LEN);
    $bannerUrl->addRule(static fn($value): bool => pf_validate_optional_url($value), _t('请填写合法的文章封面图地址'));
    $layout->addItem($bannerUrl);

    $directoryOn = new \Typecho\Widget\Helper\Form\Element\Select(
        'directoryOn',
        ['auto' => '自动（按正文标题生成）', 'on' => '强制开启', 'off' => '强制关闭'],
        'auto',
        _t('文章目录'),
        _t('控制本篇文章侧边悬浮目录的显示状态')
    );
    $layout->addItem($directoryOn);

    $pomodoroHide = new \Typecho\Widget\Helper\Form\Element\Radio(
        'pomodoroHide',
        ['false' => '正常显示', 'true' => '隐藏番茄钟'],
        'false',
        _t('隐藏番茄钟（文章页）'),
        _t('在本篇文章页面隐藏右下角番茄钟悬浮按钮')
    );
    $layout->addItem($pomodoroHide);
}
