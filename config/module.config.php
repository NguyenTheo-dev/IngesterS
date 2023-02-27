<?php

namespace IngesterS;

return [
    'media_ingesters' => [
        'factories' => [
            'canalU' => Service\Media\Ingester\CanalUFactory::class,
        ],
    ],
];