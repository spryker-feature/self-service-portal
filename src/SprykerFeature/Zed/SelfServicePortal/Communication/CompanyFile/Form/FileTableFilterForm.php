<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Form;

use DateTime;
use DateTimeZone;
use Exception;
use Generated\Shared\Transfer\FileAttachmentTableCriteriaTransfer;
use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use SprykerFeature\Zed\SelfServicePortal\Communication\CompanyFile\Form\DataProvider\FileTableFilterFormDataProvider;
use SprykerFeature\Zed\SelfServicePortal\Communication\Form\DatePickerTypeResolverTrait;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method \SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface getRepository()
 * @method \SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\SelfServicePortal\Communication\SelfServicePortalCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig getConfig()
 */
class FileTableFilterForm extends AbstractType
{
    use DatePickerTypeResolverTrait;

    /**
     * @var string
     */
    protected const RANGE_GROUP_DATE = 'ssp-file-date';

    /**
     * @var string
     */
    protected const DATE_TIME_FORMAT = 'Y-m-d\TH:i';

    /**
     * @var string
     */
    public const OPTION_EXTENSIONS = 'extensions';

    /**
     * @var string
     */
    protected const FIELD_EXTENSION = 'extension';

    /**
     * @var string
     */
    protected const FIELD_DATE_FROM = 'dateFrom';

    /**
     * @var string
     */
    protected const FIELD_DATE_TO = 'dateTo';

    /**
     * @var string
     */
    protected const LABEL_EXTENSION = 'Type';

    /**
     * @var string
     */
    protected const LABEL_DATE_FROM = 'Date From';

    /**
     * @var string
     */
    protected const LABEL_DATE_TO = 'Date To';

    /**
     * @var string
     */
    protected const PLACEHOLDER_EXTENSION = 'Select Type';

    public function getBlockPrefix(): string
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired([
            static::OPTION_EXTENSIONS,
            FileTableFilterFormDataProvider::OPTION_CURRENT_TIMEZONE,
        ]);

        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => FileAttachmentTableCriteriaTransfer::class,
        ]);
    }

    /**
     * @param array<mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addExtensionField($builder, $options)
            ->addDateFromField($builder, $options)
            ->addDateToField($builder, $options);

        $builder->setMethod(Request::METHOD_GET);
    }

    /**
     * @param array<mixed> $options
     *
     * @return $this
     */
    protected function addExtensionField(FormBuilderInterface $builder, array $options)
    {
        $builder->add(static::FIELD_EXTENSION, ChoiceType::class, [
            'choices' => $options[static::OPTION_EXTENSIONS],
            'label' => static::LABEL_EXTENSION,
            'required' => false,
            'placeholder' => static::PLACEHOLDER_EXTENSION,
        ]);

        return $this;
    }

    /**
     * @param array<mixed> $options
     *
     * @return $this
     */
    protected function addDateFromField(FormBuilderInterface $builder, array $options = [])
    {
        $timezone = $options[FileTableFilterFormDataProvider::OPTION_CURRENT_TIMEZONE];

        $builder->add(static::FIELD_DATE_FROM, $this->getDateTimeFieldType(), [
            'required' => false,
            'label' => static::LABEL_DATE_FROM,
            'model_timezone' => $timezone,
            'view_timezone' => $timezone,
        ] + $this->getDateFieldOptions(static::RANGE_GROUP_DATE, static::RANGE_ROLE_START));

        $builder->get(static::FIELD_DATE_FROM)
            ->addModelTransformer($this->createDateModelTransformer($timezone));

        return $this;
    }

    /**
     * @param array<mixed> $options
     *
     * @return $this
     */
    protected function addDateToField(FormBuilderInterface $builder, array $options = [])
    {
        $timezone = $options[FileTableFilterFormDataProvider::OPTION_CURRENT_TIMEZONE];

        $builder->add(static::FIELD_DATE_TO, $this->getDateTimeFieldType(), [
            'required' => false,
            'label' => static::LABEL_DATE_TO,
            'model_timezone' => $timezone,
            'view_timezone' => $timezone,
        ] + $this->getDateFieldOptions(static::RANGE_GROUP_DATE, static::RANGE_ROLE_END));

        $builder->get(static::FIELD_DATE_TO)
            ->addModelTransformer($this->createDateModelTransformer($timezone));

        return $this;
    }

    protected function createDateModelTransformer(string $timezone): CallbackTransformer
    {
        return new CallbackTransformer($this->parseDate($timezone), $this->formatDate());
    }

    protected function parseDate(string $timezone): callable
    {
        return function ($date) use ($timezone): ?DateTime {
            if (!$date) {
                return null;
            }

            try {
                return new DateTime($date, new DateTimeZone($timezone));
            } catch (Exception) {
                return null;
            }
        };
    }

    protected function formatDate(): callable
    {
        return fn ($date) => $date instanceof DateTime ? $date->format(static::DATE_TIME_FORMAT) : null;
    }
}
