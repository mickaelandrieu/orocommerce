<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\Product;

class LoadProductImageData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected static $products = [
        'product-1' => [
            'type' => ProductImageType::TYPE_LISTING
        ],
        'product-2' => [
            'type' => ProductImageType::TYPE_MAIN
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (static::$products as $productReference => $productData) {
            /** @var Product $product */
            $product = $this->getReference($productReference);
            $image = new File();
            $image->setFilename($product->getSku());
            $this->addReference('img.' . $product->getSku(), $image);

            $productImage = new ProductImage();
            $productImage->setImage($image);

            $productType = isset($productData['type']) ? $productData['type'] : ProductImageType::TYPE_LISTING;
            $productImage->addType($productType);

            $product->addImage($productImage);

            $manager->persist($image);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
