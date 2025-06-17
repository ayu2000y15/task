<?php

return [
    'carriers' => [
        'yamato' => [
            'name' => 'ヤマト運輸',
            'url' => 'https://jizen.kuronekoyamato.co.jp/jizen/servlet/crjz.b.NQ0010?id=',
        ],
        'sagawa' => [
            'name' => '佐川急便',
            'url' => 'https://k2k.sagawa-exp.co.jp/p/web/okurijosearch.do?okurijoNo=',
        ],
        'jp_post' => [
            'name' => '日本郵便',
            'url' => 'https://trackings.post.japanpost.jp/services/srv/search/direct?reqCodeNo1=',
        ],
        'other' => [
            'name' => 'その他',
            'url' => null, // その他は直接リンク不可
        ],
    ],
];
