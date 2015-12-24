<?php

namespace Oro\Bundle\ActionBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Form\EventListener\RequiredAttributesListener;
use Oro\Bundle\WorkflowBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class ActionType extends AbstractType
{
    const NAME = 'oro_action';

    /** @var ActionManager */
    protected $actionManager;

    /** @var RequiredAttributesListener */
    protected $requiredAttributesListener;

    /** @var ContextAccessor */
    protected $contextAccessor;

    /**
     * ActionType constructor.
     * @param ActionManager $actionManager
     * @param RequiredAttributesListener $requiredAttributesListener
     * @param ContextAccessor $contextAccessor
     */
    public function __construct(
        ActionManager $actionManager,
        RequiredAttributesListener $requiredAttributesListener,
        ContextAccessor $contextAccessor
    ) {
        $this->actionManager = $actionManager;
        $this->requiredAttributesListener = $requiredAttributesListener;
        $this->contextAccessor = $contextAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['data', 'action']);

        $resolver->setDefined(
            [
                'attribute_fields',
                'attribute_default_values',
                'init_functions'
            ]
        );

        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\ActionBundle\Model\ActionData',
                'attribute_fields' => [],
                'attribute_default_values' => []
            ]
        );

        $resolver->setAllowedTypes(
            [
                'action' => 'Oro\Bundle\ActionBundle\Model\Action',
                'attribute_fields' => 'array',
                'attribute_default_values' => 'array',
                'init_functions' => 'Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface',
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->executeInitFunctions($builder, $options);
        $this->addEventListeners($builder, $options);
        $this->addAttributes($builder, $options);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    protected function executeInitFunctions(FormBuilderInterface $builder, array $options)
    {
        /** @var ActionData $data */
        $data = $builder->getData();

        /** @var Action $action */
        $action = $options['action'];
        $action->init($data);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    protected function addEventListeners(FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['attribute_default_values'])) {
            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($options) {
                    /** @var ActionData $data */
                    $data = $event->getData();

                    foreach ($options['attribute_default_values'] as $attributeName => $value) {
                        $data->$attributeName = $this->contextAccessor->getValue($data, $value);
                    }
                }
            );
        }

        if (!empty($options['attribute_fields'])) {
            $this->requiredAttributesListener->initialize(array_keys($options['attribute_fields']));

            $builder->addEventSubscriber($this->requiredAttributesListener);
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @throws InvalidConfigurationException
     */
    protected function addAttributes(FormBuilderInterface $builder, array $options)
    {
        /** @var Action $action */
        $action = $options['action'];

        /** @var ActionData $actionData */
        $actionData = $builder->getData();

        foreach ($options['attribute_fields'] as $attributeName => $attributeOptions) {
            $attribute = $action->getAttributeManager($actionData)->getAttribute($attributeName);
            if (!$attribute) {
                throw new InvalidConfigurationException(
                    sprintf(
                        'Invalid reference to unknown attribute "%s" of action "%s".',
                        $attributeName,
                        $action->getName()
                    )
                );
            }

            if (null === $attributeOptions) {
                $attributeOptions = [];
            }

            if (isset($actionData->$attributeName)) {
                $attributeOptions['options']['data'] = $actionData->$attributeName;
            }

            $attributeOptions = $this->prepareAttributeOptions($attribute, $attributeOptions, $options);

            $builder->add($attribute->getName(), $attributeOptions['form_type'], $attributeOptions['options']);
        }
    }

    /**
     * @param Attribute $attribute
     * @param array $attributeOptions
     * @param array $options
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function prepareAttributeOptions(Attribute $attribute, array $attributeOptions, array $options)
    {
        if (empty($attributeOptions['form_type'])) {
            /** @var Action $action */
            $action = $options['action'];

            throw new InvalidConfigurationException(
                sprintf(
                    'Parameter "form_type" must be defined for attribute "%s" in action "%s".',
                    $attribute->getName(),
                    $action->getName()
                )
            );
        }

        if (!array_key_exists('options', $attributeOptions) || !is_array($attributeOptions['options'])) {
            $attributeOptions['options'] = [];
        }

        $attributeOptions['options']['label'] = isset($attributeOptions['label'])
            ? $attributeOptions['label']
            : $attribute->getLabel();

        if (!array_key_exists('required', $attributeOptions['options'])) {
            $attributeOptions['options']['required'] = false;
        }

        array_walk_recursive(
            $attributeOptions,
            function (&$leaf) use ($options) {
                $leaf = $this->contextAccessor->getValue($options['data'], $leaf);
            }
        );

        return $attributeOptions;
    }
}
