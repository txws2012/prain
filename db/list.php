<?php
//数据库结构
//#不做处理
//@数据库文件夹
return [
  '#version' => '1.1.3',
  '@article' => [
    'type' => 'files',
  ],
  '@comment' => [
    'type' => 'key-array',
    'value' => [
      'id' => 0,
      'pid' => 0,
      'admin' => 0,
      'content' => '',
      'ip' => '',
      'time' => 0,
    ],
  ],
  '@upload' => [
    'type' => 'files',
  ],
  'article' => [
    'type' => 'define-array',
    'value' => [
      'id' => '',
      'cid' => '',
      'path' => '',
      'title' => '',
      'intro' => '',
      'img' => '',
      'time' => 0,
      'isTop' => 0,
      'isPrivate' => 0,
      'isCcomment' => 1,
      'isFk' => 1,
      'views' => 0,
      'comments' => 0,
      'updateTime' => 0,
      'createTime' => 0,
      'tag' => [],
    ],
  ],
  'conf' => [
    'type' => 'array',
    'value' => [
      'title' => '',
      'name' => '',
      'intro' => '',
      'mood' => '',
      'key' => '',
      'desc' => '',
      'brief' => 40,
      'password' => '',
      'tpl' => '',
      'compile' => false,
      'debug' => 2,
      'rewrite' => false,
      'article' => [
        'paging' => 30,
        'count' => 0,
      ],
      'comment' => [
        'restrict' => 20,
        'paging' => 30,
        'count' => 0,
      ],
      'vcode' => [
        'open' => true,
        'width' => 80,
        'height' => 23,
        'length' => 4,
      ],
      'icp' => '',
      'prn' => '',
      'views' => 0,
      'blacklist' => '',
      'tag' => [],
      'ext' => ['app'=>[],'fk-editor'=>[]],
      'navbar' => [
        [
          'name' => '首页',
          'url' => '/index',
          'target' => 0,
          'child' => [],
        ],
        [
          'name' => '留言',
          'url' => '/message',
          'target' => 0,
          'child' => [],
        ],
      ],
      'category' => [
        'article' => [
          'id' => 'article',
          'name' => '文章',
          'intro' => '静水流深,沧笙踏歌',
          'count' => 0,
        ],
      ],
      'link' => [
        [
          'name' => '关于本站',
          'url' => '/about',
          'target' => 0,
        ],
      ],
      'js' => '',
      'db' => [
        'version' => '1.0.0',
      ],
      'install' => true,
    ],
  ],
  'error' => [
    'type' => 'key-array',
    'value' => [
      'ip' => '',
      'url' => '',
      'time' => 0,
      'content' => '',
    ],
  ],
  'ini' => [
    'type' => 'array',
    'value' => [],
  ],
];
?>