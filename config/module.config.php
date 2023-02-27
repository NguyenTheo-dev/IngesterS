<?php

namespace IngesterS;

return [
    'media_ingesters' => [
        'factories' => [
            'canalU' => Service\Media\Ingester\CanalUFactory::class,
        ],
    ],
    'media_renderers' => [
        'invokables' => [
            'canalu' => Media\Renderer\CanalU::class,
        ],
    ],
];