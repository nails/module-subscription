<?php

use Nails\Subscription\Model;
use Nails\Subscription\Resource;

return [
    'models'    => [
        'Instance'    => function () {
            if (class_exists('\App\Subscription\Model\Instance')) {
                return new \App\Subscription\Model\Instance();
            } else {
                return new Model\Instance();
            }
        },
        'Package'     => function () {
            if (class_exists('\App\Subscription\Model\Package')) {
                return new \App\Subscription\Model\Package();
            } else {
                return new Model\Package();
            }
        },
        'PackageCost' => function () {
            if (class_exists('\App\Subscription\Model\Package\Cost')) {
                return new \App\Subscription\Model\Package\Cost();
            } else {
                return new Model\Package\Cost();
            }
        },
    ],
    'resources' => [
        'Instance'    => function ($mObj) {
            if (class_exists('\App\Subscription\Resource\Instance')) {
                return new \App\Subscription\Resource\Instance($mObj);
            } else {
                return new Resource\Instance($mObj);
            }
        },
        'Package'     => function ($mObj) {
            if (class_exists('\App\Subscription\Resource\Package')) {
                return new \App\Subscription\Resource\Package($mObj);
            } else {
                return new Resource\Package($mObj);
            }
        },
        'PackageCost' => function ($mObj) {
            if (class_exists('\App\Subscription\Resource\Package\Cost')) {
                return new \App\Subscription\Resource\Package\Cost($mObj);
            } else {
                return new Resource\Package\Cost($mObj);
            }
        },
    ],
];