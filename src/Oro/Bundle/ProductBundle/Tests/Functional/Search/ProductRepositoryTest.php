<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Search;

use Doctrine\ORM\Query;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Search\ProductRepository as ProductSearchRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ProductRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->loadFixtures([
            LoadProductVisibilityData::class
        ]);

        $this->getContainer()->get('oro_customer.visibility.cache.product.cache_builder')->buildCache();
        $this->getContainer()->get('oro_website_search.indexer')->reindex(Product::class);
    }

    public function testSearchFilteredBySkus()
    {
        /** @var ProductRepository $ormRepository */
        $ormRepository = $this->client->getContainer()
            ->get('doctrine')
            ->getRepository(Product::class);
        /** @var ProductSearchRepository $searchRepository */
        $searchRepository = $this->client->getContainer()
            ->get('oro_product.website_search.repository.product');

        $productsFromOrm = $ormRepository->createQueryBuilder('p')
            ->setMaxResults(3)
            ->orderBy('p.id', 'desc')
            ->getQuery()
            ->getResult(Query::HYDRATE_ARRAY);

        $skus = [];
        foreach ($productsFromOrm as $productFromOrm) {
            $skus[] = $productFromOrm['sku'];
        }

        $productsFromSearch = $searchRepository->searchFilteredBySkus($skus);
        $this->assertEquals(count($productsFromOrm), count($productsFromSearch));

        foreach ($productsFromOrm as $productFromOrm) {
            $found = false;
            foreach ($productsFromSearch as $productFromSearch) {
                if ($productFromSearch->getSelectedData()['sku'] == $productFromOrm['sku']) {
                    $found = true;
                }
            }
            $this->assertTrue($found, 'Product with sku `' . $productFromOrm['sku'] . '` not found.');
        }
    }
}