<?php

namespace Nails\Subscription\Console\Command;

use Nails\Common\Exception\NailsException;
use Nails\Common\Service\ErrorHandler;
use Nails\Common\Service\Event;
use Nails\Console\Command\Base;
use Nails\Console\Exception\ConsoleException;
use Nails\Factory;
use Nails\Subscription\Constants;
use Nails\Subscription\Events;
use Nails\Subscription\Exception\RenewalException\InstanceCannotRenewException;
use Nails\Subscription\Exception\RenewalException\InstanceFailedToRenewException;
use Nails\Subscription\Exception\RenewalException\InstanceShouldNotRenewException;
use Nails\Subscription\Exception\RenewalException;
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

        $sDate = $oInput->getOption('date');
        $oDate = new \DateTime($sDate);

        $oOutput->writeln(
            sprintf(
                'Fetching instances due to renew on <info>%s</info>',
                $oDate->format('Y-m-d')
            )
        );
        $aRenewals = $this->getRenewals($oDate);

        if (!empty($aRenewals) && $this->confirmRenewals($aRenewals)) {
            $this->processRenewals($aRenewals, $oDate);

        } elseif (empty($aRenewals)) {
            $oOutput->writeln('No renewals to be processed');
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
    protected function getRenewals(\DateTime $oWhen): array
    {
        /** @var Subscription $oSubscription */
        $oSubscription = Factory::service('Subscription', Constants::MODULE_SLUG);
        return $oSubscription->getRenewals($oWhen);
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
     * @param \DateTime  $oWhen
     *
     * @throws \Nails\Common\Exception\FactoryException
     */
    protected function processRenewals(array $aInstances, \DateTime $oWhen): self
    {
        $this->oOutput->writeln('');

        /** @var Subscription $oSubscription */
        $oSubscription = Factory::service('Subscription', Constants::MODULE_SLUG);

        foreach ($aInstances as $oInstance) {
            try {

                /**
                 * Set a string to group all the lgos for this particular renewal together.
                 * This allows for easier log searching as you can find all logs which
                 * apply to a particular action by grep'ing by the log group
                 */
                $oSubscription
                    ->setLogGroup(uniqid())
                    ->log()
                    ->log('Beginning Renewal of Instance #' . $oInstance->id);

                $this->oOutput->write('Renewing instance <info>#' . $oInstance->id . '</info>... ');
                $oNewInstance = $oSubscription->renew($oInstance, false);
                $this->oOutput->writeln(
                    sprintf(
                        '<info>success</info> – New Instance: #%s; Invoice: #%s %s',
                        $oNewInstance->id,
                        $oNewInstance->invoice()->id,
                        $oNewInstance->invoice()->ref
                    )
                );

            } catch (InstanceShouldNotRenewException $e) {

                /**
                 * Instances which fire this flavour of exception should not have
                 * any form of renewal attempted. This isn't necessrily an error,
                 * it could be the instance has been configured not to renew. We
                 * want to fire an event anyway to let the app decide how it wishes
                 * to handle this scenario. e.g. send a "your subscription has ended"
                 * or a "you have been downgraded" email to the customer.
                 */

                $this->logAndTriggerEvent($oSubscription, $e, Events::RENEWAL_INSTANCE_SHOULD_NOT_RENEW, false);

            } catch (InstanceCannotRenewException $e) {

                /**
                 * Instances which fire this flavour of exception want to rewnew,
                 * but can't due to knowledge that the renewal will fail, e.g.
                 * their card is missing, or has expired.
                 */

                $this->logAndTriggerEvent($oSubscription, $e, Events::RENEWAL_INSTANCE_CANNOT_RENEW, false);

            } catch (InstanceFailedToRenewException $e) {

                /**
                 * The system attempted to process the renewal, but failed (most
                 * likely due to payment failure). Details about the specific
                 * reason for failure can be inferred from the exception.
                 */

                $this->logAndTriggerEvent($oSubscription, $e, Events::RENEWAL_INSTANCE_FAILED_TO_RENEW, true);

            } catch (\Exception $e) {

                /**
                 * Something unexpected happened. We don't want to stop the renewal
                 * loop, but we do need to do something with this information. Offload
                 * to the error handler, but instruct it not to halt execution.
                 */

                $this->logAndTriggerEvent($oSubscription, $e, Events::RENEWAL_UNCAUGHT_EXCEPTION, true);

                /** @var ErrorHandler $oErrorHandler */
                $oErrorHandler = Factory::service('ErrorHandler');
                call_user_func($oErrorHandler::getDriverClass() . '::exception', $e, false);

            } finally {
                $oSubscription
                    ->log('Completed Renewal of Instance #' . $oInstance->id)
                    ->log();
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Utility method which logs an exception to the output and triggers an event
     *
     * @param Subscription $oSubscription  The subscriptions ervice
     * @param \Exception   $e              The exception which was caught
     * @param string       $sEvent         The event to fire
     * @param bool         $bBothInstances Whether to include both instances in the event data
     *
     * @throws NailsException
     * @throws \ReflectionException
     */
    protected function logAndTriggerEvent(
        Subscription $oSubscription,
        \Exception $e,
        string $sEvent,
        ?bool $bBothInstances = false
    ) {

        $sLine = sprintf(
            'ERROR: %s (%s)',
            $e->getMessage(),
            get_class($e)
        );

        $oSubscription->log($sLine);
        $this->oOutput->writeln('<error>' . $sLine . '</error>');

        $aPayload = $bBothInstances
            ? [
                is_callable([$e, 'getInstance']) ? $e->getInstance() : null,
                is_callable([$e, 'getNewInstance']) ? $e->getNewInstance() : null,
                $e,
            ]
            : [
                is_callable([$e, 'getInstance']) ? $e->getInstance() : null,
                $e,
            ];

        $oSubscription->log(sprintf(
            'Triggering Event: %s (%s)',
            $sEvent,
            Events::getEventNamespace()
        ));

        /** @var Event $oEventService */
        $oEventService = Factory::service('Event');
        $oEventService
            ->trigger(
                $sEvent,
                Events::getEventNamespace(),
                $aPayload
            );
    }
}
