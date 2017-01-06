<?php

namespace Oro\Bundle\DPDBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DPDBundle\Model\Package;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;

class PackageProvider
{
    const MAX_PACKAGE_WEIGHT_KGS = 31.5; //as defined on dpd api documentation
    const UNIT_OF_WEIGHT = 'kg'; //dpd only supports kg

    /** @var ManagerRegistry */
    protected $registry;

    /** @var MeasureUnitConversion */
    protected $measureUnitConversion;

    public function __construct(
        ManagerRegistry $registry,
        MeasureUnitConversion $measureUnitConversion
    ){
        $this->registry = $registry;
        $this->measureUnitConversion = $measureUnitConversion;
    }

    /**
     * @param ShippingContextInterface $context
     * @return \Oro\Bundle\DPDBundle\Model\Package[]|null
     */
    public function createPackages(ShippingContextInterface $context)
    {
        if ($context->getLineItems()->isEmpty()) {
            return null;
        }

        $packages = [];
        $productsWeightByUnit = $this->getProductWeightByUnit($context->getLineItems());
        if (count($productsWeightByUnit) > 0) {
            $weight = 0;
            /** @var array $weightParams */
            foreach ($productsWeightByUnit as $unit) {
                if ($unit['weight'] > static::MAX_PACKAGE_WEIGHT_KGS) {
                    return null;
                }
                if (($weight + $unit['weight']) > static::MAX_PACKAGE_WEIGHT_KGS) {
                    $packages[] = (new Package)
                        ->setWeight((string)$weight);

                    $weight = 0;
                }

                $weight += $unit['weight'];
            }

            if ($weight > 0) {
                $packages[] = (new Package)
                    ->setWeight((string)$weight);
            }
        }

        return $packages;
    }

    /**
     * @param ShippingLineItemCollectionInterface $lineItems
     * @return array
     * @throws \UnexpectedValueException
     */
    protected function getProductWeightByUnit($lineItems)
    {
        $productsWeightByUnit = [];

        $productsInfo =[];
        foreach ($lineItems as $lineItem) {
            $productsInfo[$lineItem->getProduct()->getId()] = [
                'product' => $lineItem->getProduct(),
                'productUnit' => $lineItem->getProductUnit(),
                'quantity' => $lineItem->getQuantity()
            ];
        }

        $allProductsShippingOptions = $this->registry
            ->getManagerForClass('OroShippingBundle:ProductShippingOptions')
            ->getRepository('OroShippingBundle:ProductShippingOptions')
            ->findBy([
                'product' => array_column($productsInfo, 'product'),
                'productUnit' => array_column($productsInfo, 'productUnit')
            ]);

        if (!$allProductsShippingOptions ||
            count(array_column($productsInfo, 'product')) !== count($allProductsShippingOptions)) {
            return [];
        }

        /** @var ProductShippingOptions $productShippingOptions */
        foreach ($allProductsShippingOptions as $productShippingOptions) {
            $productId = $productShippingOptions->getProduct()->getId();

            $lineItemWeight = null;
            if ($productShippingOptions->getWeight() instanceof Weight) {
                if (!$productShippingOptions->getWeight()->getValue()) {
                    return [];
                }
                /** @var Weight|null $lineItemWeight */
                $lineItemWeight = $this->measureUnitConversion->convert(
                    $productShippingOptions->getWeight(),
                    static::UNIT_OF_WEIGHT
                );

                $lineItemWeight = $lineItemWeight !== null ? $lineItemWeight->getValue() : null;
            }
            if (!$lineItemWeight) {
                return [];
            }

            for ($i = 0; $i < $productsInfo[$productId]['quantity']; $i++) {
                $productsWeightByUnit[] = [
                    'weightUnit' => static::UNIT_OF_WEIGHT,
                    'weight' => $lineItemWeight
                ];
            }
        }

        return $productsWeightByUnit;
    }
}