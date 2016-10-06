<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method static ContainerInterface getContainer
 */
trait MessageQueueTrait
{
    use MessageQueueAssertTrait;

    protected function cleanScheduledMessages()
    {
        $this->sendScheduledMessages();
        $this->getMessageCollector()->enable();
        $this->getMessageCollector()->clear();
    }

    protected function sendScheduledMessages()
    {
        self::getContainer()->get('oro_account.visibility_message_handler')
            ->sendScheduledMessages();
    }
}