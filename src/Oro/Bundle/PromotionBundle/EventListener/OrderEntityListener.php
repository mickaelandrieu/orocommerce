<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;

class OrderEntityListener
{
    /**
     * @var AppliedDiscountManager
     */
    protected $appliedDiscountManager;

    /**
     * @param AppliedDiscountManager $appliedDiscountManager
     */
    public function __construct(AppliedDiscountManager $appliedDiscountManager)
    {
        $this->appliedDiscountManager = $appliedDiscountManager;
    }

    /**
     * @param Order $order
     * @param LifecycleEventArgs $args
     */
    public function prePersist(Order $order, LifecycleEventArgs $args)
    {
        $this->appliedDiscountManager->saveAppliedDiscounts($order);
    }

    /**
     * @param Order $order
     */
    public function preRemove(Order $order)
    {
        $this->appliedDiscountManager->removeAppliedDiscountByOrder($order);
    }
}
