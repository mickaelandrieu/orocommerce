<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\ProductRepository;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\AccountBundle\Visibility\Cache\Product\ProductResolvedCacheBuilder;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class ProductResolvedCacheBuilderTest extends AbstractCacheBuilderTest
{
    public function testChangeProductVisibilityToHidden()
    {
        // main product visibility entity
        $visibility = new ProductVisibility();
        $visibility->setWebsite($this->website);
        $visibility->setProduct($this->product);
        $visibility->setVisibility(ProductVisibility::HIDDEN);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->persist($visibility);
        $entityManager->flush();

        $resolvedVisibility = $this->getVisibilityResolved();
        $this->assertNotNull($resolvedVisibility);
        $this->assertStatic($resolvedVisibility, $visibility, BaseProductVisibilityResolved::VISIBILITY_HIDDEN);
    }

    /**
     * @depends testChangeProductVisibilityToHidden
     */
    public function testChangeProductVisibilityToVisible()
    {
        $visibility = $this->getVisibility();
        $this->assertNotNull($visibility);

        $visibility->setVisibility(ProductVisibility::VISIBLE);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        $resolvedVisibility = $this->getVisibilityResolved();
        $this->assertNotNull($resolvedVisibility);
        $this->assertStatic($resolvedVisibility, $visibility, BaseProductVisibilityResolved::VISIBILITY_VISIBLE);
    }

    /**
     * @depends testChangeProductVisibilityToVisible
     */
    public function testChangeProductVisibilityToConfig()
    {
        $visibility = $this->getVisibility();
        $this->assertNotNull($visibility);
        $this->assertNotNull($this->getVisibilityResolved());

        $visibility->setVisibility(ProductVisibility::CONFIG);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->flush();

        $this->assertNull($this->getVisibilityResolved());
    }

    /**
     * @depends testChangeProductVisibilityToConfig
     */
    public function testChangeProductVisibilityToCategory()
    {
        $visibility = $this->getVisibility();
        $this->assertNotNull($visibility);

        $visibility->setVisibility(ProductVisibility::CATEGORY);

        $entityManager = $this->getManagerForVisibility();
        $entityManager->remove($visibility);
        $entityManager->flush();

        $resolvedVisibility = $this->getVisibilityResolved();
        $this->assertNotNull($resolvedVisibility);
        $this->assertEquals(
            $resolvedVisibility->getCategory()->getId(),
            $this->getReference(LoadCategoryData::FIRST_LEVEL)->getId()
        );
        $this->assertEquals($resolvedVisibility->getSource(), BaseProductVisibilityResolved::SOURCE_CATEGORY);
        $this->assertNull($resolvedVisibility->getSourceProductVisibility());
        $this->assertEquals(
            $resolvedVisibility->getVisibility(),
            BaseProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG
        );
        $this->assertProductIdentifyEntitiesAccessory($resolvedVisibility);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCacheDataProvider()
    {
        return [
            'without_website' => [
                'expectedStaticCount' => 3,
                'expectedCategoryCount' => 24,
                'websiteReference' => null,
            ],
            'with_website1' => [
                'expectedStaticCount' => 0,
                'expectedCategoryCount' => 0,
                'websiteReference' => LoadWebsiteData::WEBSITE1,
            ],
            'with_website2' => [
                'expectedStaticCount' => 0,
                'expectedCategoryCount' => 0,
                'websiteReference' => LoadWebsiteData::WEBSITE2,
            ],
        ];
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForVisibility()
    {
        return $this->registry->getManagerForClass('OroAccountBundle:Visibility\ProductVisibility');
    }

    /**
     * @return EntityManager
     */
    protected function getManagerForVisibilityResolved()
    {
        return $this->registry->getManagerForClass('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved');
    }

    /**
     * @return ProductVisibility|null
     */
    protected function getVisibility()
    {
        return $this->registry->getManagerForClass('OroAccountBundle:Visibility\ProductVisibility')
            ->getRepository('OroAccountBundle:Visibility\ProductVisibility')
            ->findOneBy(['website' => $this->website, 'product' => $this->product]);
    }

    /**
     * @return ProductVisibilityResolved|null
     */
    protected function getVisibilityResolved()
    {
        $entityManager = $this->getManagerForVisibilityResolved();
        $entity = $entityManager
            ->getRepository('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findByPrimaryKey($this->product, $this->website);

        if ($entity) {
            $entityManager->refresh($entity);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSourceRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroAccountBundle:Visibility\ProductVisibility'
        );
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroAccountBundle:VisibilityResolved\ProductVisibilityResolved'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheBuilder()
    {
        $container = $this->client->getContainer();

        $builder = new ProductResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_entity.orm.insert_from_select_query_executor')
        );
        $builder->setCacheClass(
            $container->getParameter('oro_account.entity.product_visibility_resolved.class')
        );

        return $builder;
    }
}