<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\DPDBundle\Entity\Repository\RateRepository;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Provider\RateProvider;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class RateProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DPDTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var RateProvider
     */
    protected $rateProvider;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var MeasureUnitConversion|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $measureUnitConversion;

    protected function setUp()
    {
        $this->transport = $this->createMock(DPDTransport::class);

        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()->getMock();

        $this->measureUnitConversion = $this->getMockBuilder(MeasureUnitConversion::class)
            ->disableOriginalConstructor()->getMock();
        $this->measureUnitConversion->expects(static::any())->method('convert')->willReturnCallback(
            function () {
                $args = func_get_args();

                return $args[0];
            }
        );

        $this->rateProvider = new RateProvider(
            $this->registry,
            $this->measureUnitConversion
        );
    }

    public function testGetRateValueFlatPolicy()
    {
        $this->transport->expects(self::any())->method('getRatePolicy')->willReturn(DPDTransport::FLAT_RATE_POLICY);
        $this->transport->expects(self::once())->method('getFlatRatePriceValue')->willReturn('1.0');

        static::assertEquals(
            '1.0',
            $this->rateProvider->getRateValue($this->transport, new ShippingService(), new OrderAddress(), null)
        );
    }

    public function testGetRateValueTablePolicy()
    {
        $this->transport->expects(self::any())->method('getRatePolicy')->willReturn(DPDTransport::TABLE_RATE_POLICY);

        $rates = [
            (new Rate())->setPriceValue('1.0'),
            (new Rate())->setPriceValue('2.0'),
        ];

        $repository = $this->createMock(RateRepository::class);
        $repository->expects(self::any())->method('findRatesByServiceAndDestination')->willReturn($rates);

        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects(self::any())->method('getRepository')->willReturn($repository);

        $this->registry->expects(self::any())->method('getManagerForClass')->willReturn($manager);

        static::assertEquals(
            '1.0',
            $this->rateProvider->getRateValue($this->transport, new ShippingService(), new OrderAddress(), null)
        );
    }
}
