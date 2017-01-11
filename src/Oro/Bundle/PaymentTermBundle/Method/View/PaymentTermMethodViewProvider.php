<?php

namespace Oro\Bundle\PaymentTermBundle\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Symfony\Component\Translation\TranslatorInterface;

class PaymentTermMethodViewProvider implements PaymentMethodViewProviderInterface
{
    /** @var  PaymentMethodViewInterface[] */
    protected $methodViews;
    
    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /**  @var TranslatorInterface */
    protected $translator;

    /** @var PaymentTermConfigInterface */
    protected $config;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param TranslatorInterface $translator
     * @param PaymentTermConfigInterface $config
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        TranslatorInterface $translator,
        PaymentTermConfigInterface $config
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->translator = $translator;
        $this->config = $config;
    }

    /**
     * @param array $paymentMethods
     * @return PaymentMethodViewInterface[]
     */
    public function getPaymentMethodViews(array $paymentMethods)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        $matchedViews = [];
        foreach ($paymentMethods as $paymentMethod) {
            $matchedViews[$paymentMethod] = $this->getPaymentMethodView($paymentMethod);
        }
        return $matchedViews;
    }

    /**
     * @param string $identifier
     * @return PaymentMethodViewInterface
     */
    public function getPaymentMethodView($identifier)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        return $this->methodViews[$identifier];
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentMethodView($identifier)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        return array_key_exists($identifier, $this->methodViews);
    }

    protected function collectPaymentMethodViews()
    {
        $methodView = new PaymentTermView($this->paymentTermProvider, $this->translator, $this->config);
        $this->methodViews = [PaymentTerm::TYPE => $methodView];
    }
}
