<?php

// +----------------------------------------------------------------------
// | 接口设置
// +----------------------------------------------------------------------

return [
    'images' => [
        'code' => 'Images',
        'method' => [
            'upload' => [
                'code' => 'upload',
                'is_login' => false
            ],
        ]
    ],
    //会员
    'member' => [
        'code' => 'member',
        'method' => [
            'wxlogin' =>[
                'code' => 'Wxlogin',
                'is_login' => false
            ],
            'savemember' => [
                'code' => 'SaveMember',
                'is_login' => false
            ],
            'savememberzyq' => [
                'code' => 'saveMemberzyq',
                'is_login' => false
            ],
            'userinfo' => [
                'code' => 'userinfo',
                'is_login' => true
            ],
            'getverity' => [
                'code' => 'getverity',
                'is_login' => true
            ],
            'chechcode' => [
                'code' => 'chechcode',
                'is_login' => true
            ],
            'postgift' => [
                'code' => 'postgift',
                'is_login' => true
            ]
        ]
    ],
    //春团
    'spinggroup' => [
        'code' => 'SpingGroup',
        'method' => [
            'getlist' => [
                'code' => 'getList',
                'is_login' => false
            ],
            'kaituan' => [
                'code' => 'kaituan',
                'is_login' => true
            ],
            'checkgroup' => [
                'code' => 'checkGroup',
                'is_login' => true
            ],
            'gettuaninfo' => [
                'code' => 'gettuanInfo',
                'is_login' => true
            ],
            'zhuli' => [
                'code' => 'zhuli',
                'is_login' => true
            ],
            'getzhuliindex' => [
                'code' => 'getzhuliIndex',
                'is_login' => false
            ]
        ]
    ],
    //海报
    'poster' => [
        'code' => 'Poster',
        'method' => [
            'geterweima'=>[
                'code' => 'geterweima',
                'is_login' => true
            ]
        ]
    ],
    //规则
    'rule' => [
        'code' => 'Rule',
        'method' => [
            'getlist' => [
                'code' => 'getlist',
                'is_login' => false
            ]
        ]
    ],
    //领取记录
    'giftlog' =>[
        'code' => 'GiftLog',
        'method' => [
            'getmygift' => [
                'code' => 'getMygift',
                'is_login' => true
            ],
            'drawgift' => [
                'code' => 'drawgift',
                'is_login' => true
            ]
        ]
    ],
    /**礼卷*/
    'gift' => [
        'code' => 'Gift',
        'method' => [
            'getlist' => [
                'code' => 'getlist',
                'is_login' => false
            ]
        ]
    ],
    /**广告*/
    'advimg' => [
        'code' => 'Advimg',
        'method' => [
            'getadvimglist' => [
                'code' => 'getadvimglist',
                'is_login' => false
            ]
        ]
    ]
];