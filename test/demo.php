<?php

require __DIR__ . '/../vendor/autoload.php';
define('URL_YOUDU_API', '');
define('BUIN', 0);
define('KEY', '');
define('APP_ID', '');

try {
    $ydSdk = new \Jerryaicn2023\Youdu\Youdu(URL_YOUDU_API, BUIN, APP_ID, KEY);
    //$ydSdk->debug();

    $crypt = new \Jerryaicn2023\Youdu\Crypt(KEY);
    var_dump($ydSdk->sendPop(
        json_encode(
            [
                'toUser' => '30015',
                'popWindow' =>
                    [
                        'url' => 'http://youdu.im',
                        'tip' => '',
                        'title' => '应用弹窗',
                        'width' => 400,
                        'height' => 400,
                        'duration' => -1,
                        'position' => 3,
                        'notice_id' => '{202D2081-5ADA-4FE5-81B8-53D88E1FD016}',
                        'pop_mode' => 2,
                    ]
            ]
        )
    ));
    var_dump($ydSdk->sendImage('30015', realpath(__DIR__ . '/demo.jpg')));
    var_dump($ydSdk->sendFile('30015', realpath(__DIR__ . '/demo.jpg')));
    var_dump($ydSdk->sendText('30015', "晚上好"));
    var_dump(
        $ydSdk->sendNews('30015',
            [
                [
                    'title' => "百度",
                    'media_id' => realpath(__DIR__ . '/demo.jpg'),
                    'digest' => "百度一下，你就知道",
                    'showFront' => 1,
                    'content' => '<b>百度（Baidu）</b>是拥有强大互联网基础的领先AI公司。百度愿景是：成为最懂用户，并能帮助人们成长的全球顶级高科技公司。 [1]
“百度”二字，来自于八百年前南宋词人辛弃疾的一句词：众里寻他千百度。这句话描述了词人对理想的执着追求。1999年底，身在美国硅谷的李彦宏看到了中国互联网及中文搜索引擎服务的巨大发展潜力，抱着技术改变世界的梦想，他毅然辞掉硅谷的高薪工作，携搜索引擎专利技术，于 2000年1月1日在中关村创建了百度公司。
百度拥有数万名研发工程师，这是中国乃至全球都顶尖的技术团队。这支队伍掌握着世界上领先的搜索引擎技术，使百度成为掌握世界尖端科学核心技术的中国高科技企业，也使中国成为美国、俄罗斯和韩国之外，全球仅有的4个拥有搜索引擎核心技术的国家之一。 [1]',
                    'url' => 'https://www.baidu.com',
                ],
                [
                    'title' => "必应",
                    'media_id' => realpath(__DIR__ . '/demo.jpg'),
                    'digest' => "搜索一下，必须响应", 'showFront' => 1,
                    'content' => '微软必应（英文名：Microsoft Bing），原名必应（Bing），是微软公司于2009年5月28日推出，用以取代Live Search的全新搜索引擎服务。为符合中国用户使用习惯，Bing中文品牌名为“必应”。',
                    'url' => 'https://www.bing.com',
                ],
                [
                    'title' => "搜狗",
                    'media_id' => realpath(__DIR__ . '/demo.jpg'),
                    'digest' => "搜狗搜索引擎 - 上网从搜狗开始", 'showFront' => 1,
                    'content' => '搜狗 [1]原是搜狐公司的旗下子公司，于2004年8月3日推出，目的是增强搜狐网的搜索技能，主要经营搜狐公司的搜索业务。在搜索业务的同时，也推出搜狗输入法、搜狗高速浏览器。
2022年8月16日12点，搜狗页游（含手机页游）全面停止服务。公司将协助客户与游戏官方对接，沟通转服事宜；如用户有尚未消耗的购买游戏币且决定不转服，可在2022年11月16日12点前联系搜狗游戏服务中心客服退款。 [53]',
                    'url' => 'https://www.sogou.com',
                ]
            ]
        )
    );
    var_dump(
        $ydSdk->sendLink('30015',
            [
                [
                    'title' => "百度",
                    'media_id' => realpath(__DIR__ . '/demo.jpg'),
                    'digest' => "百度一下，你就知道",
                    'url' => 'https://www.baidu.com',
                ],
                [
                    'title' => "必应",
                    'media_id' => realpath(__DIR__ . '/demo.jpg'),
                    'digest' => "搜索一下，必须响应",
                    'url' => 'https://www.bing.com',
                ],
                [
                    'title' => "搜狗",
                    'media_id' => realpath(__DIR__ . '/demo.jpg'),
                    'digest' => "搜狗搜索引擎 - 上网从搜狗开始",
                    'url' => 'https://www.sogou.com',
                ]
            ]
        )
    );
} catch (\Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
}