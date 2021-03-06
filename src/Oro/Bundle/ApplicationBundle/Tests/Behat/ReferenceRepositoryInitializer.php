<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Behat;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeRepository;
use Oro\Bundle\DPDBundle\Entity\ShippingService as DPDShippingService;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\ReferenceRepositoryInitializer as BaseInitializer;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCurrency;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

class ReferenceRepositoryInitializer extends BaseInitializer
{
    public function init()
    {
        parent::init();
        /** @var EntityRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroAddressBundle:Country');
        /** @var Country $germany */
        $germany = $repository->findOneBy(['name' => 'Germany']);
        $this->referenceRepository->set('germany', $germany);
        /** @var Country $us */
        $us = $repository->findOneBy(['name' => 'United States']);
        $this->referenceRepository->set('united_states', $us);

        /** @var Country $unitedStates */
        $unitedStates = $repository->findOneBy(['name' => 'United States']);
        $this->referenceRepository->set('us', $unitedStates);

        /** @var RegionRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroAddressBundle:Region');
        /** @var Region $berlin */
        $berlin = $repository->findOneBy(['name' => 'Berlin']);
        $this->referenceRepository->set('berlin', $berlin);

        /** @var Region $florida */
        $florida = $repository->findOneBy(['name' => 'Florida']);
        $this->referenceRepository->set('florida', $florida);

        /** @var CustomerUserRoleRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroCustomerBundle:CustomerUserRole');
        /** @var CustomerUserRole buyer */
        $buyer = $repository->findOneBy(['role' => 'ROLE_FRONTEND_BUYER']);
        $this->referenceRepository->set('buyer', $buyer);

        /** @var CustomerUserRole $administrator */
        $administrator = $repository->findOneBy(['role' => 'ROLE_FRONTEND_ADMINISTRATOR']);
        $this->referenceRepository->set('front_admin', $administrator);

        /** @var ProductUnitRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroProductBundle:ProductUnit');
        /** @var ProductUnit $item */
        $item = $repository->findOneBy(['code' => 'item']);
        $this->referenceRepository->set('item', $item);
        /** @var ProductUnit $each */
        $each = $repository->findOneBy(['code' => 'each']);
        $this->referenceRepository->set('each', $each);
        /** @var ProductUnit $set */
        $set = $repository->findOneBy(['code' => 'set']);
        $this->referenceRepository->set('set', $set);

        /** @var AddressTypeRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroAddressBundle:AddressType');
        /** @var AddressType $billingType*/
        $billingType = $repository->findOneBy(['name' => 'billing']);
        $this->referenceRepository->set('billingType', $billingType);
        /** @var AddressType $shippingType*/
        $shippingType = $repository->findOneBy(['name' => 'shipping']);
        $this->referenceRepository->set('shippingType', $shippingType);

        /** @var EntityRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroPricingBundle:PriceListCurrency');
        /** @var PriceListCurrency EUR*/
        $eur = $repository->findOneBy(['currency' => 'EUR']);
        $this->referenceRepository->set('eur', $eur);

        /** @var PriceListRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroPricingBundle:PriceList');
        /** @var PriceList $pricelist1*/
        $pricelist1 = $repository->findOneBy(['id' => '1']);
        $this->referenceRepository->set('defaultPriceList', $pricelist1);

        /** @var WebsiteRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroWebsiteBundle:Website');
        /** @var Website $website1*/
        $website1 = $repository->findOneBy(['id' => '1']);
        $this->referenceRepository->set('website1', $website1);

        /** @var CombinedPriceListRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroPricingBundle:CombinedPriceList');
        /** @var CombinedPriceList $combinedPriceList*/
        $combinedPriceList = $repository->findOneBy(['id' => '1']);
        $this->referenceRepository->set('combinedPriceList', $combinedPriceList);

        $this->configureDictionaries();
    }

    protected function configureDictionaries()
    {
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        /** @var AbstractEnumValue[] $enumInventoryStatuses */
        $enumInventoryStatuses = $this->getEntityManager()
            ->getRepository($inventoryStatusClassName)
            ->findAll();

        $inventoryStatuses = [];
        foreach ($enumInventoryStatuses as $inventoryStatus) {
            $inventoryStatuses[$inventoryStatus->getId()] = $inventoryStatus;
        }

        $this->referenceRepository->set(
            'enumInventoryStatuses',
            $inventoryStatuses[Product::INVENTORY_STATUS_IN_STOCK]
        );

        $this->referenceRepository->set(
            'enumInventoryStatusOutOfStock',
            $inventoryStatuses[Product::INVENTORY_STATUS_OUT_OF_STOCK]
        );

        // move to DPDBundle after https://magecore.atlassian.net/browse/BAP-15050 will be done
        /** @var EntityRepository $repository */
        $repository = $this->getEntityManager()->getRepository('OroDPDBundle:ShippingService');
        /** @var DPDShippingService $germany */
        $germany = $repository->findOneBy(['code' => DPDShippingService::CLASSIC_SERVICE_SUBSTR]);
        $this->referenceRepository->set('dpdClassicShippingService', $germany);

        $types = $this->getEntityManager()->getRepository(SegmentType::class)->findAll();
        foreach ($types as $type) {
            $this->referenceRepository->set(sprintf('segment_%s_type', strtolower($type->getName())), $type);
        }
    }
}
