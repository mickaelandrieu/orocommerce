<?php

namespace Oro\Bundle\OrderBundle\RequestHandler;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class FrontendOrderDataHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var PaymentTermProvider  */
    protected $paymentTermProvider;

    /**
     * @param ManagerRegistry $registry
     * @param RequestStack $requestStack
     * @param SecurityFacade $securityFacade
     * @param PaymentTermProvider $paymentTermProvider
     */
    public function __construct(
        ManagerRegistry $registry,
        RequestStack $requestStack,
        SecurityFacade $securityFacade,
        PaymentTermProvider $paymentTermProvider
    ) {
        $this->registry = $registry;
        $this->requestStack = $requestStack;
        $this->securityFacade = $securityFacade;
        $this->paymentTermProvider = $paymentTermProvider;
    }

    /**
     * @return Account|null
     */
    public function getAccount()
    {
        $accountUser = $this->getAccountUser();

        return $accountUser->getAccount();
    }

    /**
     * @return CustomerUser
     */
    public function getAccountUser()
    {
        $accountUser = $this->securityFacade->getLoggedUser();
        if (!$accountUser instanceof CustomerUser) {
            throw new \InvalidArgumentException('Only CustomerUser can create an Order');
        }

        return $accountUser;
    }

    /**
     * @return null|PaymentTerm
     */
    public function getPaymentTerm()
    {
        return $this->paymentTermProvider->getPaymentTerm($this->getAccount());
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        //TODO: BB-3825 set correct owner
        return $this->registry->getManagerForClass('OroUserBundle:User')
            ->getRepository('OroUserBundle:User')
            ->findOneBy([]);
    }
}
