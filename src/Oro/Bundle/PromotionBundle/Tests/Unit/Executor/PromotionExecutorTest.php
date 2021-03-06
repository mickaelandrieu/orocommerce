<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Executor;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\Converter\DiscountContextConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountFactory;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyInterface;
use Oro\Bundle\PromotionBundle\Discount\Strategy\StrategyProvider;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProvider;
use Oro\Bundle\PromotionBundle\Provider\PromotionProvider;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class PromotionExecutorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PromotionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $promotionProvider;

    /**
     * @var DiscountContextConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $discountContextConverter;

    /**
     * @var DiscountFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $discountFactory;

    /**
     * @var StrategyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $discountStrategyProvider;

    /**
     * @var MatchingProductsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $matchingProductsProvider;

    /**
     * @var PromotionExecutor
     */
    private $executor;

    protected function setUp()
    {
        $this->promotionProvider = $this->createMock(PromotionProvider::class);
        $this->discountContextConverter = $this->createMock(DiscountContextConverterInterface::class);
        $this->discountFactory = $this->createMock(DiscountFactory::class);
        $this->discountStrategyProvider = $this->createMock(StrategyProvider::class);
        $this->matchingProductsProvider = $this->createMock(MatchingProductsProvider::class);

        $this->executor = new PromotionExecutor(
            $this->promotionProvider,
            $this->discountContextConverter,
            $this->discountFactory,
            $this->discountStrategyProvider,
            $this->matchingProductsProvider
        );
    }

    public function testExecuteNoDiscounts()
    {
        $sourceEntity = new \stdClass();
        $discountContext = new DiscountContext();

        $this->discountContextConverter->expects($this->once())
            ->method('convert')
            ->with($sourceEntity)
            ->willReturn($discountContext);

        $this->promotionProvider->expects($this->once())
            ->method('getPromotions')
            ->with($sourceEntity)
            ->willReturn([]);

        $this->discountFactory->expects($this->never())
            ->method($this->anything());

        $this->discountStrategyProvider->expects($this->never())
            ->method($this->anything());

        $this->assertSame($discountContext, $this->executor->execute($sourceEntity));
    }

    public function testExecute()
    {
        /** @var Segment $segment */
        $segment = $this->createMock(Segment::class);
        $lineItems = [$this->createMock(DiscountLineItem::class)];
        $matchedProducts = [$this->createMock(Product::class)];

        $sourceEntity = new \stdClass();
        /** @var DiscountContext|\PHPUnit_Framework_MockObject_MockObject $discountContext */
        $discountContext = $this->createMock(DiscountContext::class);
        $discountContext->expects($this->once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $discountConfiguration = $this->createMock(DiscountConfiguration::class);
        /** @var DiscountInterface|\PHPUnit_Framework_MockObject_MockObject $discount */
        $discount = $this->createMock(DiscountInterface::class);
        $discount->expects($this->once())
            ->method('setMatchingProducts')
            ->willReturn($matchedProducts);

        $promotion = $this->createMock(Promotion::class);
        $promotion->expects($this->once())
            ->method('getDiscountConfiguration')
            ->willReturn($discountConfiguration);
        $promotion->expects($this->once())
            ->method('getProductsSegment')
            ->willReturn($segment);

        $this->discountContextConverter->expects($this->once())
            ->method('convert')
            ->with($sourceEntity)
            ->willReturn($discountContext);

        $this->promotionProvider->expects($this->once())
            ->method('getPromotions')
            ->with($sourceEntity)
            ->willReturn([$promotion]);

        $this->discountFactory->expects($this->once())
            ->method('create')
            ->with($discountConfiguration)
            ->willReturn($discount);

        $this->matchingProductsProvider->expects($this->once())
            ->method('getMatchingProducts')
            ->with($segment, $lineItems)
            ->willReturn($matchedProducts);

        $newContext = new DiscountContext();
        $newContext->addSubtotalDiscount($discount);

        $discountStrategy = $this->createMock(StrategyInterface::class);
        $this->discountStrategyProvider->expects($this->once())
            ->method('getActiveStrategy')
            ->willReturn($discountStrategy);
        $discountStrategy->expects($this->once())
            ->method('process')
            ->with($discountContext, [$discount])
            ->willReturn($newContext);

        $this->assertSame($newContext, $this->executor->execute($sourceEntity));
    }

    /**
     * @dataProvider trueFalseDataProvider
     * @param bool $result
     */
    public function testSupports($result)
    {
        $entity = new \stdClass();
        $this->discountContextConverter->expects($this->once())
            ->method('supports')
            ->willReturn($result);

        $this->assertSame($result, $this->executor->supports($entity));
    }

    /**
     * @return array
     */
    public function trueFalseDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }
}
