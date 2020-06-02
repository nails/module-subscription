<?php

use Nails\Subscription\Model;
use Nails\Subscription\Resource;
use Nails\Subscription\Service;

return [
    'services'  => [
        'Subscription' => function (): Service\Subscription {
            if (class_exists('\App\Subscription\Service\Subscription')) {
                return new \App\Subscription\Service\Subscription();
            } else {
                return new Service\Subscription();
            }
        },
    ],
    'models'    => [
        'Instance'    => function (): Model\Instance {
            if (class_exists('\App\Subscription\Model\Instance')) {
                return new \App\Subscription\Model\Instance();
            } else {
                return new Model\Instance();
            }
        },
        'Package'     => function (): Model\Package {
            if (class_exists('\App\Subscription\Model\Package')) {
                return new \App\Subscription\Model\Package();
            } else {
                return new Model\Package();
            }
        },
        'PackageCost' => function (): Model\Package\Cost {
            if (class_exists('\App\Subscription\Model\Package\Cost')) {
                return new \App\Subscription\Model\Package\Cost();
            } else {
                return new Model\Package\Cost();
            }
        },
    ],
    'resources' => [
        'Instance'             => function ($mObj): Resource\Instance {
            if (class_exists('\App\Subscription\Resource\Instance')) {
                return new \App\Subscription\Resource\Instance($mObj);
            } else {
                return new Resource\Instance($mObj);
            }
        },
        'InstanceCallbackData' => function (): Resource\Instance\CallbackData {
            if (class_exists('\App\Subscription\Resource\Instance\CallbackData')) {
                return new \App\Subscription\Resource\Instance\CallbackData();
            } else {
                return new Resource\Instance\CallbackData();
            }
        },
        'InstanceSummary'      => function ($mObj): Resource\Instance\Summary {
            if (class_exists('\App\Subscription\Resource\Instance\Summary')) {
                return new \App\Subscription\Resource\Instance\Summary($mObj);
            } else {
                return new Resource\Instance\Summary($mObj);
            }
        },
        'Package'              => function ($mObj): Resource\Package {
            if (class_exists('\App\Subscription\Resource\Package')) {
                return new \App\Subscription\Resource\Package($mObj);
            } else {
                return new Resource\Package($mObj);
            }
        },
        'PackageCost'          => function ($mObj): Resource\Package\Cost {
            if (class_exists('\App\Subscription\Resource\Package\Cost')) {
                return new \App\Subscription\Resource\Package\Cost($mObj);
            } else {
                return new Resource\Package\Cost($mObj);
            }
        },
    ],
];
