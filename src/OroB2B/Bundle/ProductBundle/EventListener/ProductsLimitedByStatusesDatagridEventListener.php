<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

use OroB2B\Bundle\ProductBundle\Event\ProductSelectQueryEvent;

class ProductsLimitedByStatusesDatagridEventListener
{
    /** @var  RequestStack */
    protected $requestStack;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param RequestStack $requestStack
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(RequestStack $requestStack, EventDispatcherInterface $eventDispatcher)
    {
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $dataSource */
        $dataSource = $event->getDatagrid()->getAcceptedDatasource();
        $qb = $dataSource->getQueryBuilder();
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $params = $request->get(ProductSelectType::DATA_PARAMETERS)) {
            $this->eventDispatcher->dispatch(ProductSelectQueryEvent::NAME, new ProductSelectQueryEvent($qb, $params));
        }
    }
}
