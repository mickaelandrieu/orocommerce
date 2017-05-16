<?php

namespace Oro\Bundle\InfinitePayBundle\Form\Type;

use Oro\Bundle\InfinitePayBundle\Entity\InfinitePaySettings;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class InfinitePaySettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_infinitepay_settings';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var SymmetricCrypterInterface
     */
    protected $encoder;


    /**
     * @param TranslatorInterface                 $translator
     * @param SymmetricCrypterInterface           $encoder
     */
    public function __construct(
        TranslatorInterface $translator,
        SymmetricCrypterInterface $encoder
    ) {
        $this->translator = $translator;
        $this->encoder = $encoder;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws ConstraintDefinitionException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     * @throws \InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('infinitePayLabels', LocalizedFallbackValueCollectionType::NAME, [
                'label' => 'oro.infinite_pay.settings.labels.label',
                'required' => true,
                'options' => ['constraints' => [new NotBlank()]],
            ])
            ->add('infinitePayShortLabels', LocalizedFallbackValueCollectionType::NAME, [
                'label' => 'oro.infinite_pay.settings.short_labels.label',
                'required' => true,
                'options' => ['constraints' => [new NotBlank()]],
            ])
            ->add('infinitePayClientRef', TextType::class, [
                'label' => 'oro.infinite_pay.settings.client_ref.label',
                'required' => true,
            ])
            ->add('infinitePayUsername', TextType::class, [
                'label' => 'oro.infinite_pay.settings.username.label',
                'required' => true,
            ])
            ->add('infinitePayPassword', PasswordType::class, [
                'label' => 'oro.infinite_pay.settings.password.label',
                'required' => true,
            ])
            ->add('infinitePaySecret', PasswordType::class, [
                'label' => 'oro.infinite_pay.settings.secret.label',
                'required' => true,
            ])
            ->add('infinitePayAutoCapture', CheckboxType::class, [
                'label' => 'oro.infinite_pay.settings.auto_capture.label'
            ])
            ->add('infinitePayAutoActivate', CheckboxType::class, [
                'label' => 'oro.infinite_pay.settings.auto_activate.label'
            ])
            ->add('infinitePayTestMode', CheckboxType::class, [
                'label' => 'oro.infinite_pay.settings.test_mode.label'
            ])
            ->add('infinitePayDebugMode', CheckboxType::class, [
                'label' => 'oro.infinite_pay.settings.debug_mode.label'
            ])
            ->add('infinitePayInvoiceDuePeriod', IntegerType::class, [
                'label' => 'oro.infinite_pay.settings.invoice_due_period.label'
            ])
            ->add('infinitePayInvoiceShippingDuration', IntegerType::class, [
                'label' => 'oro.infinite_pay.settings.invoice_shipping_duration.label'
            ]);
        $this->transformWithEncodedValue($builder, 'infinitePayPassword');
        $this->transformWithEncodedValue($builder, 'infinitePaySecret');
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InfinitePaySettings::class,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param string               $field
     * @param bool                 $decrypt
     *
     * @throws \InvalidArgumentException
     */
    protected function transformWithEncodedValue(FormBuilderInterface $builder, $field, $decrypt = true)
    {
        $builder->get($field)->addModelTransformer(new CallbackTransformer(
            function ($value) use ($decrypt) {
                if ($decrypt === true) {
                    return $this->encoder->decryptData($value);
                }

                return $value;
            },
            function ($value) {
                return $this->encoder->encryptData($value);
            }
        ));
    }
}
