<?php

namespace OroB2B\Bundle\CheckoutBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;

/**
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-shopping-cart"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id",
 *              "frontend_owner_type"="FRONTEND_USER",
 *              "frontend_owner_field_name"="accountUser",
 *              "frontend_owner_column_name"="account_user_id",
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          },
 *          "workflow"={
 *              "active_workflow"="b2b_flow_checkout"
 *          }
 *      }
 * )
 */
class Checkout extends AbstractCheckout implements LineItemsNotPricedAwareInterface
{
    use CheckoutAddressesTrait;

    /**
     * @var Order
     *
     * @ORM\OneToOne(targetEntity="OroB2B\Bundle\OrderBundle\Entity\Order")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=true)
     */
    protected $order;

    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        /** @var LineItemsNotPricedAwareInterface|LineItemsAwareInterface $sourceEntity */
        $sourceEntity = $this->getSourceEntity();
        return $sourceEntity && ($sourceEntity instanceof LineItemsNotPricedAwareInterface
            || $sourceEntity instanceof LineItemsAwareInterface) ? $sourceEntity->getLineItems() : [];
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     * @return Checkout
     */
    public function setOrder(Order $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return '';
    }
}
