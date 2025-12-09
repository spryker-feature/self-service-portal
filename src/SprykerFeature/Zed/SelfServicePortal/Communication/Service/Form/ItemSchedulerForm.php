<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Communication\Service\Form;

use DateTime;
use DateTimeZone;
use Generated\Shared\Transfer\ItemMetadataTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use SprykerFeature\Zed\SelfServicePortal\Communication\Service\Form\DataProvider\ItemSchedulerFormDataProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @method \SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\SelfServicePortal\Communication\SelfServicePortalCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig getConfig()
 * @method \SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface getRepository()
 */
class ItemSchedulerForm extends AbstractType
{
    public const string FIELD_SCHEDULED_AT = 'scheduledAt';

    protected const string FIELD_LABEL_SCHEDULED_AT = 'Date and time';

    protected const string DATE_TIME_FORMAT_HTML5 = 'Y-m-d\TH:i';

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array<string, mixed> $options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addScheduledAtField($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            ItemSchedulerFormDataProvider::OPTION_CURRENT_TIMEZONE,
        ]);

        $resolver->setDefaults([
            'data_class' => ItemTransfer::class,
        ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    protected function addScheduledAtField(FormBuilderInterface $builder, array $options = [])
    {
        $builder->add(static::FIELD_SCHEDULED_AT, DateTimeType::class, [
            'label' => static::FIELD_LABEL_SCHEDULED_AT,
            'widget' => 'single_text',
            'required' => true,
            'view_timezone' => $options[ItemSchedulerFormDataProvider::OPTION_CURRENT_TIMEZONE],
            'constraints' => [
                new NotBlank(),
                new GreaterThan([
                    'value' => (new DateTime('now'))->format(DateTime::ISO8601),
                    'message' => 'Service date must be in the future',
                ]),
            ],
            'attr' => [
                'min' => (new DateTime('now', new DateTimeZone($options[ItemSchedulerFormDataProvider::OPTION_CURRENT_TIMEZONE])))
                    ->format(static::DATE_TIME_FORMAT_HTML5),
            ],
            'property_path' => ItemTransfer::METADATA . '.' . ItemMetadataTransfer::SCHEDULED_AT,
        ]);

        $this->addScheduledAtTransformer($builder);

        return $this;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     *
     * @return $this
     */
    protected function addScheduledAtTransformer(FormBuilderInterface $builder)
    {
        $builder->get(static::FIELD_SCHEDULED_AT)
            ->addModelTransformer(new CallbackTransformer(
                function ($dateAsString): DateTime|null {
                    if (!$dateAsString) {
                        return null;
                    }

                    return new DateTime($dateAsString);
                },
                function ($dateAsObject): string|null {
                    /** @var \DateTime|null $dateAsObject */
                    if (!$dateAsObject) {
                        return null;
                    }

                    return $dateAsObject->format(DateTime::ISO8601);
                },
            ));

        return $this;
    }
}
