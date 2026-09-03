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
use SprykerFeature\Zed\SelfServicePortal\Communication\Form\DatePickerTypeResolverTrait;
use SprykerFeature\Zed\SelfServicePortal\Communication\Service\Form\DataProvider\ItemSchedulerFormDataProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
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
    use DatePickerTypeResolverTrait;

    public const string FIELD_SCHEDULED_AT = 'scheduledAt';

    protected const string FIELD_LABEL_SCHEDULED_AT = 'Date and time';

    protected const string DATE_TIME_FORMAT_HTML5 = 'Y-m-d\TH:i';

    /**
     * @param array<string, mixed> $options
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
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    protected function addScheduledAtField(FormBuilderInterface $builder, array $options = [])
    {
        $builder->add(static::FIELD_SCHEDULED_AT, $this->getDateTimeFieldType(), [
            'label' => static::FIELD_LABEL_SCHEDULED_AT,
            'required' => true,
            'view_timezone' => $options[ItemSchedulerFormDataProvider::OPTION_CURRENT_TIMEZONE],
            'constraints' => [
                new NotBlank(),
                new GreaterThan([
                    'value' => (new DateTime('now'))->format(DateTime::ISO8601),
                    'message' => 'Service date must be in the future',
                ]),
            ],
            'property_path' => ItemTransfer::METADATA . '.' . ItemMetadataTransfer::SCHEDULED_AT,
        ] + $this->getScheduledAtLowerBoundOptions($options[ItemSchedulerFormDataProvider::OPTION_CURRENT_TIMEZONE]));

        $this->addScheduledAtTransformer($builder);

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScheduledAtLowerBoundOptions(string $timezone): array
    {
        $lowerBound = (new DateTime('now', new DateTimeZone($timezone)))->format(static::DATE_TIME_FORMAT_HTML5);

        if ($this->isGuiDateTimePickerTypeAvailable()) {
            return ['min_date' => $lowerBound];
        }

        return [
            'widget' => 'single_text',
            'attr' => ['min' => $lowerBound],
        ];
    }

    /**
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
