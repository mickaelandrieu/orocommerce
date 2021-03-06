<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;

class ProductRepository extends EntityRepository
{
    /**
     * @param string $sku
     *
     * @return null|Product
     */
    public function findOneBySku($sku)
    {
        $queryBuilder = $this->createQueryBuilder('product');

        $queryBuilder->andWhere('product.skuUppercase = :sku')
            ->setParameter('sku', strtoupper($sku));

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $pattern
     *
     * @return string[]
     */
    public function findAllSkuByPattern($pattern)
    {
        $matchedSku = [];

        $results = $this
            ->createQueryBuilder('product')
            ->select('product.sku')
            ->where('product.sku LIKE :pattern')
            ->setParameter('pattern', $pattern)
            ->getQuery()
            ->getResult();

        foreach ($results as $result) {
            $matchedSku[] = $result['sku'];
        }

        return $matchedSku;
    }

    /**
     * @param array $productIds
     *
     * @return QueryBuilder
     */
    public function getProductsQueryBuilder(array $productIds = [])
    {
        $productsQueryBuilder = $this
            ->createQueryBuilder('p')
            ->select('p');

        if (count($productIds) > 0) {
            $productsQueryBuilder
                ->where($productsQueryBuilder->expr()->in('p.id', ':productIds'))
                ->setParameter('productIds', $productIds);
        }

        return $productsQueryBuilder;
    }

    /**
     * @param array $productSkus
     *
     * @return array Ids
     */
    public function getProductsIdsBySku(array $productSkus = [])
    {
        $productsQueryBuilder = $this
            ->createQueryBuilder('p')
            ->select('p.id, p.sku');

        if ($productSkus) {
            // Convert to uppercase for insensitive search in all DB
            $upperCaseSkus = array_map("strtoupper", $productSkus);

            $productsQueryBuilder
                ->where($productsQueryBuilder->expr()->in('p.skuUppercase', ':product_skus'))
                ->setParameter('product_skus', $upperCaseSkus);
        }

        $productsData = $productsQueryBuilder
            ->orderBy($productsQueryBuilder->expr()->asc('p.id'))
            ->getQuery()
            ->getArrayResult();

        $productsSkusToIds = [];
        foreach ($productsData as $key => $productData) {
            $productsSkusToIds[$productData['sku']] = $productData['id'];
            unset($productsData[$key]);
        }

        return $productsSkusToIds;
    }

    /**
     * @param string $search
     * @param int $firstResult
     * @param int $maxResults
     * @return QueryBuilder
     */
    public function getSearchQueryBuilder($search, $firstResult, $maxResults)
    {
        $productsQueryBuilder = $this
            ->createQueryBuilder('p');

        $productsQueryBuilder
            ->innerJoin('p.names', 'pn', Expr\Join::WITH, $productsQueryBuilder->expr()->isNull('pn.localization'))
            ->where(
                $productsQueryBuilder->expr()->orX(
                    $productsQueryBuilder->expr()->like('LOWER(p.sku)', ':search'),
                    $productsQueryBuilder->expr()->like('LOWER(pn.string)', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($search) . '%')
            ->addOrderBy('p.id')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        return $productsQueryBuilder;
    }

    /**
     * @return QueryBuilder
     */
    public function getProductWithNamesQueryBuilder()
    {
        $queryBuilder = $this->createQueryBuilder('product')
            ->select('product');
        $this->selectNames($queryBuilder);
        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return $this
     */
    public function selectNames(QueryBuilder $queryBuilder)
    {
        $queryBuilder
            ->addSelect('product_names')
            ->innerJoin(
                'product.names',
                'product_names',
                Expr\Join::WITH,
                $queryBuilder->expr()->isNull('product_names.localization')
            );

        return $this;
    }

    /**
     * @param array $skus
     * @return QueryBuilder
     */
    public function getProductWithNamesBySkuQueryBuilder(array $skus)
    {
        // Convert to uppercase for insensitive search in all DB
        $upperCaseSkus = array_map("strtoupper", $skus);

        $qb = $this->getProductWithNamesQueryBuilder();
        $qb->where($qb->expr()->in('product.skuUppercase', ':product_skus'))
            ->setParameter('product_skus', $upperCaseSkus);

        return $qb;
    }

    /**
     * @param array $skus
     * @return Product[]
     */
    public function getProductWithNamesBySku(array $skus)
    {
        $qb = $this->getProductWithNamesBySkuQueryBuilder($skus);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $skus
     * @return QueryBuilder
     */
    public function getFilterSkuQueryBuilder(array $skus)
    {
        // Convert to uppercase for insensitive search in all DB
        $upperCaseSkus = array_map("strtoupper", $skus);

        $queryBuilder = $this->createQueryBuilder('product');
        $queryBuilder
            ->select('product.sku')
            ->where($queryBuilder->expr()->in('product.skuUppercase', ':product_skus'))
            ->setParameter('product_skus', $upperCaseSkus);
        return $queryBuilder;
    }

    /**
     * @param array $skus
     * @return QueryBuilder
     */
    public function getFilterProductWithNamesQueryBuilder(array $skus)
    {
        return $this->getFilterSkuQueryBuilder($skus)->select('product, product_names')
            ->innerJoin('product.names', 'product_names');
    }

    /**
     * @param array $productIds
     * @return File[]
     */
    public function getListingImagesFilesByProductIds(array $productIds)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('imageFile as image, IDENTITY(pi.product) as product_id')
            ->from('OroAttachmentBundle:File', 'imageFile')
            ->join(
                'OroProductBundle:ProductImage',
                'pi',
                Expr\Join::WITH,
                'imageFile.id = pi.image'
            );

        $qb->where($qb->expr()->in('pi.product', ':products'))
            ->setParameter('products', $productIds);

        $qb->join('pi.types', 'imageTypes')
            ->andWhere($qb->expr()->eq('imageTypes.type', ':imageType'))
            ->setParameter('imageType', ProductImageType::TYPE_LISTING);

        $productImages = $qb->getQuery()->execute();
        $images = [];

        foreach ($productImages as $productImage) {
            $images[$productImage['product_id']] = $productImage['image'];
        }

        return $images;
    }

    /**
     * @param int $productId
     *
     * @return File[]
     */
    public function getImagesFilesByProductId($productId)
    {
        $qb = $this->_em->createQueryBuilder()
                        ->select('imageFile')
                        ->from(File::class, 'imageFile')
                        ->join(
                            ProductImage::class,
                            'pi',
                            Expr\Join::WITH,
                            'imageFile.id = pi.image'
                        );

        $qb->where($qb->expr()->eq('pi.product', ':product_id'))
           ->setParameter('product_id', $productId);

        return $qb->getQuery()->execute();
    }

    /**
     * @param string $sku
     * @return string|null
     */
    public function getPrimaryUnitPrecisionCode($sku)
    {
        $qb = $this->createQueryBuilder('product');

        return $qb
            ->select('IDENTITY(productPrecision.unit)')
            ->innerJoin('product.primaryUnitPrecision', 'productPrecision')
            ->where($qb->expr()->eq('product.skuUppercase', ':sku'))
            ->setParameter('sku', strtoupper($sku))
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * @param array $ids
     * @return Product[]
     */
    public function getProductsByIds(array $ids)
    {
        return $this->getProductsQueryBuilder($ids)->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    private function filterByImageType(QueryBuilder $queryBuilder)
    {
        $queryBuilder->addSelect('product_images, product_images_types, product_images_file')
            ->join('product.images', 'product_images')
            ->join('product_images.types', 'product_images_types')
            ->join('product_images.image', 'product_images_file')
            ->andWhere($queryBuilder->expr()->eq('product_images_types.type', ':imageType'))
            ->setParameter('imageType', ProductImageType::TYPE_LISTING);
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * $variantParameters = [
     *     'size' => 'm',
     *     'color' => 'red',
     *     'slim_fit' => true
     * ]
     * Value is extended field id for select field and true or false for boolean field
     * @return QueryBuilder
     */
    public function getSimpleProductsByVariantFieldsQueryBuilder(Product $configurableProduct, array $variantParameters)
    {
        $qb = $this
            ->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.parentVariantLinks', 'l')
            ->andWhere('l.parentProduct = :parentProduct')
            ->setParameter('parentProduct', $configurableProduct);

        foreach ($variantParameters as $variantName => $variantValue) {
            $qb
                ->andWhere(sprintf('p.%s = :variantValue%s', $variantName, $variantName))
                ->setParameter(sprintf('variantValue%s', $variantName), $variantValue);
        }

        return $qb;
    }

    /**
     * @param array $criteria
     * @return Product[]
     * @throws \LogicException
     */
    public function findByCaseInsensitive(array $criteria)
    {
        $queryBuilder = $this->createQueryBuilder('product');

        foreach ($criteria as $fieldName => $fieldValue) {
            if (!is_string($fieldValue)) {
                throw new \LogicException(sprintf('Value of %s must be string', $fieldName));
            }

            $parameterName = $fieldName . 'Value';
            $queryBuilder
                ->andWhere("UPPER(product.$fieldName) = :$parameterName")
                ->setParameter($parameterName, mb_strtoupper($fieldValue));
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int $quantity
     *
     * @return QueryBuilder
     */
    public function getFeaturedProductsQueryBuilder($quantity)
    {
        $queryBuilder = $this->getProductWithNamesQueryBuilder()
            ->setMaxResults($quantity)
            ->orderBy('product.id', 'ASC');
        $this->filterByImageType($queryBuilder);

        return $queryBuilder;
    }

    /**
     * @param $type
     * @param $fieldName
     * @param $fieldValue
     * @param $isRelationField
     * @return mixed
     */
    public function findByAttributeValue($type, $fieldName, $fieldValue, $isRelationField)
    {
        if ($isRelationField) {
            return $this->createQueryBuilder('p')
                ->select('p')
                ->join('p.' . $fieldName, 'attr')
                ->where('attr = :valueId')
                ->setParameter('valueId', $fieldValue)
                ->andWhere('p.type = :type')
                ->setParameter('type', $type)
                ->getQuery()
                ->getResult();
        } else {
            return $this->findBy([
                'type' => $type,
                $fieldName => $fieldValue
            ]);
        }
    }
}
