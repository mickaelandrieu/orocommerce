<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\DiscountConfiguration;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\RuleFiltration\CurrencyFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class CurrencyFiltrationServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $filtrationService;

    /**
     * @var CurrencyFiltrationService
     */
    protected $currencyFiltrationService;

    protected function setUp()
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);

        $this->currencyFiltrationService = new CurrencyFiltrationService(
            $this->filtrationService
        );
    }

    /**
     * @dataProvider getFilteredRuleOwnersDataProvider
     *
     * @param array $context
     * @param array $ruleOwners
     * @param array $expected
     */
    public function testGetFilteredRuleOwners(array $context, array $ruleOwners, array $expected)
    {
        $this->filtrationService->expects($this->once())
            ->method('getFilteredRuleOwners')
            ->with($expected, $context)
            ->willReturn($expected);

        $this->currencyFiltrationService->getFilteredRuleOwners($ruleOwners, $context);
    }

    /**
     * @return array
     */
    public function getFilteredRuleOwnersDataProvider(): array
    {
        $promotion = new Promotion();
        $promotion->setDiscountConfiguration(
            (new DiscountConfiguration())
                ->setOptions([
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'EUR'
                ])
        );

        $promotionWithPercentTypeDiscount = new Promotion();
        $promotionWithPercentTypeDiscount->setDiscountConfiguration(
            (new DiscountConfiguration())
                ->setOptions([
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_PERCENT,
                ])
        );

        $promotionWithAnotherCurrencyDiscount = new Promotion();
        $promotionWithAnotherCurrencyDiscount->setDiscountConfiguration(
            (new DiscountConfiguration())
                ->setOptions([
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD'
                ])
        );

        return [
            'Applicable promotion' => [
                'context' => ['currency' => 'EUR'],
                'ruleOwners' => [$promotion],
                'expected' => [$promotion]
            ],
            'Promotion with percent type discount' => [
                'context' => ['currency' => 'EUR'],
                'ruleOwners' => [$promotionWithPercentTypeDiscount],
                'expected' => [$promotionWithPercentTypeDiscount]
            ],
            'Promotion with another currency discount' => [
                'context' => ['currency' => 'EUR'],
                'ruleOwners' => [$promotionWithAnotherCurrencyDiscount],
                'expected' => []
            ],
            'Several rule owners' => [
                'context' => ['currency' => 'EUR'],
                'ruleOwners' => [
                    $promotion,
                    $promotionWithPercentTypeDiscount,
                    $promotionWithAnotherCurrencyDiscount
                ],
                'expected' => [
                    $promotion,
                    $promotionWithPercentTypeDiscount
                ]
            ]
        ];
    }
}
