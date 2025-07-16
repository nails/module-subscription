<?php

use Nails\Subscription\Model;
use Nails\Subscription\Resource;
use Nails\Subscription\Service;
use Nails\Subscription\Factory;

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
        'Log'         => function (): Model\Log {
            if (class_exists('\App\Subscription\Model\Log')) {
                return new \App\Subscription\Model\Log();
            } else {
                return new Model\Log();
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
    'factories' => [
        'EmailInstanceCancelled'      => function (): Factory\Email\Instance\Cancelled {
            if (class_exists('\App\Subscription\Factory\Email\Instance\Cancelled')) {
                return new \App\Subscription\Factory\Email\Instance\Cancelled();
            } else {
                return new Factory\Email\Instance\Cancelled();
            }
        },
        'EmailInstanceCreated'        => function (): Factory\Email\Instance\Created {
            if (class_exists('\App\Subscription\Factory\Email\Instance\Created')) {
                return new \App\Subscription\Factory\Email\Instance\Created();
            } else {
                return new Factory\Email\Instance\Created();
            }
        },
        'EmailInstanceModified'       => function (): Factory\Email\Instance\Modified {
            if (class_exists('\App\Subscription\Factory\Email\Instance\Modified')) {
                return new \App\Subscription\Factory\Email\Instance\Modified();
            } else {
                return new Factory\Email\Instance\Modified();
            }
        },
        'EmailInstanceRenewCannot'    => function (): Factory\Email\Instance\Renew\Cannot {
            if (class_exists('\App\Subscription\Factory\Email\Instance\Renew\Cannot')) {
                return new \App\Subscription\Factory\Email\Instance\Renew\Cannot();
            } else {
                return new Factory\Email\Instance\Renew\Cannot();
            }
        },
        'EmailInstanceRenewFailed'    => function (): Factory\Email\Instance\Renew\Failed {
            if (class_exists('\App\Subscription\Factory\Email\Instance\Renew\Failed')) {
                return new \App\Subscription\Factory\Email\Instance\Renew\Failed();
            } else {
                return new Factory\Email\Instance\Renew\Failed();
            }
        },
        'EmailInstanceRenewFailedSca' => function (): Factory\Email\Instance\Renew\FailedSca {
            if (class_exists('\App\Subscription\Factory\Email\Instance\Renew\FailedSca')) {
                return new \App\Subscription\Factory\Email\Instance\Renew\FailedSca();
            } else {
                return new Factory\Email\Instance\Renew\FailedSCA();
            }
        },
        'EmailInstanceRenewOk'        => function (): Factory\Email\Instance\Renew\Ok {
            if (class_exists('\App\Subscription\Factory\Email\Instance\Renew\Ok')) {
                return new \App\Subscription\Factory\Email\Instance\Renew\Ok();
            } else {
                return new Factory\Email\Instance\Renew\Ok();
            }
        },
        'EmailInstanceRenewWillNot'   => function (): Factory\Email\Instance\Renew\WillNot {
            if (class_exists('\App\Subscription\Factory\Email\Instance\Renew\WillNot')) {
                return new \App\Subscription\Factory\Email\Instance\Renew\WillNot();
            } else {
                return new Factory\Email\Instance\Renew\WillNot();
            }
        },
        'EmailInstanceRestored'       => function (): Factory\Email\Instance\Restored {
            if (class_exists('\App\Subscription\Factory\Email\Instance\Restored')) {
                return new \App\Subscription\Factory\Email\Instance\Restored();
            } else {
                return new Factory\Email\Instance\Restored();
            }
        },
        'EmailInstanceSwapped'        => function (): Factory\Email\Instance\Swapped {
            if (class_exists('\App\Subscription\Factory\Email\Instance\Swapped')) {
                return new \App\Subscription\Factory\Email\Instance\Swapped();
            } else {
                return new Factory\Email\Instance\Swapped();
            }
        },
        'EmailInstanceTerminated'     => function (): Factory\Email\Instance\Terminated {
            if (class_exists('\App\Subscription\Factory\Email\Instance\Terminated')) {
                return new \App\Subscription\Factory\Email\Instance\Terminated();
            } else {
                return new Factory\Email\Instance\Terminated();
            }
        },
    ],
    'resources' => [
        'Instance'                  => function ($resource, $model): Resource\Instance {
            if (class_exists('\App\Subscription\Resource\Instance')) {
                return new \App\Subscription\Resource\Instance($resource, $model);
            } else {
                return new Resource\Instance($resource, $model);
            }
        },
        'InstanceCallbackData'      => function ($resource, $model = null): Resource\Instance\CallbackData {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Subscription\Resource\Instance\CallbackData')) {
                return new \App\Subscription\Resource\Instance\CallbackData();
            } else {
                return new Resource\Instance\CallbackData();
            }
        },
        'InstanceSummary'           => function ($resource, $model = null): Resource\Instance\Summary {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Subscription\Resource\Instance\Summary')) {
                return new \App\Subscription\Resource\Instance\Summary($resource);
            } else {
                return new Resource\Instance\Summary($resource);
            }
        },
        'InstanceSummaryThirdParty' => function ($resource, $model = null): Resource\Instance\Summary\ThirdParty {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Subscription\Resource\Instance\Summary\ThirdParty')) {
                return new \App\Subscription\Resource\Instance\Summary\ThirdParty($resource);
            } else {
                return new Resource\Instance\Summary\ThirdParty($resource);
            }
        },
        'Log'                       => function ($resource, $model): Resource\Log {
            if (class_exists('\App\Subscription\Resource\Log')) {
                return new \App\Subscription\Resource\Log($resource, $model);
            } else {
                return new Resource\Log($resource, $model);
            }
        },
        'Package'                   => function ($resource, $model): Resource\Package {
            if (class_exists('\App\Subscription\Resource\Package')) {
                return new \App\Subscription\Resource\Package($resource, $model);
            } else {
                return new Resource\Package($resource, $model);
            }
        },
        'PackageCost'               => function ($resource, $model): Resource\Package\Cost {
            if (class_exists('\App\Subscription\Resource\Package\Cost')) {
                return new \App\Subscription\Resource\Package\Cost($resource, $model);
            } else {
                return new Resource\Package\Cost($resource, $model);
            }
        },
    ],
];
