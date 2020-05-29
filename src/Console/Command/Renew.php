<?php

namespace Nails\Subscription\Console\Command;

use Nails\Console\Command\Base;
use Nails\Console\Exception\ConsoleException;
use Nails\Factory;
use Nails\Subscription\Constants;
use Nails\Subscription\Resource\Instance;
use Nails\Subscription\Service\Subscription;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Renew
 *
 * @package Nails\Subscription\Console\Command
 */
class Renew extends Base
{
    /**
     * Configures the command
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('subscription:renew')
            ->setDescription('Processes subscription renewals')
            ->addOption('date', 'd', InputOption::VALUE_REQUIRED, 'Date renewals are due');
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the app
     *
     * @param InputInterface  $oInput  The Input Interface provided by Symfony
     * @param OutputInterface $oOutput The Output Interface provided by Symfony
     *
     * @return void
     * @throws \Exception
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        parent::execute($oInput, $oOutput);

        $this->banner('Subscription Renewals');

        $aRenewals = $this->getRenewals();
        if ($this->confirmRenewals($aRenewals)) {
            $this->processRenewals($aRenewals);
        }

        //  And we're done!
        $oOutput->writeln('');
        $oOutput->writeln('Complete!');

        return static::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns instances which are due to renew
     *
     * @return Instance[]
     * @throws \Nails\Common\Exception\FactoryException
     */
    protected function getRenewals(): array
    {
        /** @var Subscription $oSubscription */
        $oSubscription = Factory::service('Subscription', Constants::MODULE_SLUG);

        $sDate = $this->oInput->getOption('date');
        $oDate = new \DateTime($sDate);

        return $oSubscription->getRenewals($oDate);
    }

    // --------------------------------------------------------------------------

    /**
     * @param Instance[] $aInstances
     *
     * @return bool
     */
    protected function confirmRenewals(array $aInstances): bool
    {
        $this->oOutput->writeln('The following instances are due to be renewed:');

        foreach ($aInstances as $oInstance) {
            $this->keyValueList([
                'Instance ID'   => $oInstance->id,
                'Customer ID'   => $oInstance->customer()->id ?? 'undefined',
                'Customer Name' => $oInstance->customer()->label ?? 'undefined',
                'Old Package'   => $oInstance->package()->label ?? 'undefined',
                'New Package'   => $oInstance->changeToPackage()->label ?? $oInstance->package()->label ?? 'undefined',
            ], true, false);
        }

        $this->oOutput->writeln('');

        return $this->confirm('Continue?', true);
    }

    // --------------------------------------------------------------------------

    /**
     * Processes each renewal
     *
     * @param Instance[] $aInstances
     *
     * @throws \Nails\Common\Exception\FactoryException
     */
    protected function processRenewals(array $aInstances): self
    {
        $this->oOutput->writeln('');

        /** @var Subscription $oSubscription */
        $oSubscription = Factory::service('Subscription', Constants::MODULE_SLUG);

        foreach ($aInstances as $oInstance) {
            try {
                $this->oOutput->write('Renewing instance <info>#' . $oInstance->id . '</info>... ');
                $oSubscription->renew($oInstance);
                $this->oOutput->writeln('<info>done</info>');
            } catch (\Exception $e) {
                $this->oOutput->writeln('<error>ERROR: ' . $e->getMessage() . '</error>');
            }
        }

        return $this;
    }
}
