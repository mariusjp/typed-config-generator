<?php

return [
    'skip_deep_dive' => [
        'app' => [
            'providers',
            'aliases',
        ],
        'cache.memcached' => [
            'servers',
        ],
        'filesystems' => [
            'links',
        ],
    ],
];
