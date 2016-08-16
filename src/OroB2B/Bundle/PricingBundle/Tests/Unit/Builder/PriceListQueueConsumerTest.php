<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\Common\Persistence\ObjectManager;
use OroB2B\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use OroB2B\Bundle\PricingBundle\Builder\ProductPriceBuilder;
use OroB2B\Bundle\PricingBundle\Builder\PriceRuleQueueConsumer;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceRuleChangeTriggerRepository;

class PriceRuleQueueConsumerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var PriceListProductAssignmentBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productAssignmentBuilder;

    /**
     * @var ProductPriceBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productPriceBuilder;

    /**
     * @var PriceRuleQueueConsumer
     */
    protected $priceListQueueConsumer;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->productAssignmentBuilder = $this->getMockBuilder(PriceListProductAssignmentBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productPriceBuilder = $this->getMockBuilder(ProductPriceBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceListQueueConsumer = new PriceRuleQueueConsumer(
            $this->registry,
            $this->productAssignmentBuilder,
            $this->productPriceBuilder
        );
    }

    public function testProcess()
    {
        $this->productAssignmentBuilder->expects($this->once())->method('buildByPriceList');
        $this->productPriceBuilder->expects($this->once())->method('buildByPriceList');
        $manager = $this->getMock(ObjectManager::class);
        $repository = $this->getMockBuilder(PriceRuleChangeTriggerRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $trigger = new PriceRuleChangeTrigger(new PriceList());

        $repository->method('getTriggersIterator')->willReturn([$trigger]);
        $manager->method('getRepository')->willReturn($repository);
        $this->registry->method('getManagerForClass')->willReturn($manager);

        $this->priceListQueueConsumer->process();
    }
}