<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Communication\Asset\Form;

use Generated\Shared\Transfer\CompanyBusinessUnitCriteriaFilterTransfer;
use Generated\Shared\Transfer\CompanyBusinessUnitTransfer;
use Generated\Shared\Transfer\CompanyCriteriaFilterTransfer;
use Generated\Shared\Transfer\SspAssetTransfer;
use Spryker\Zed\Gui\Communication\Form\Type\Select2ComboBoxType;
use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

/**
 * @method \SprykerFeature\Zed\SelfServicePortal\Communication\SelfServicePortalCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\SelfServicePortal\SelfServicePortalConfig getConfig()
 * @method \SprykerFeature\Zed\SelfServicePortal\Business\SelfServicePortalFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\SelfServicePortal\Persistence\SelfServicePortalRepositoryInterface getRepository()
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SspAssetForm extends AbstractType
{
    /**
     * @var string
     */
    protected const FIELD_NAME = 'name';

    /**
     * @var string
     */
    public const FIELD_BUSINESS_UNIT_OWNER = 'companyBusinessUnit';

    /**
     * @var string
     */
    protected const FIELD_SERIAL_NUMBER = 'serialNumber';

    /**
     * @var string
     */
    protected const FIELD_STATUS = 'status';

    /**
     * @var string
     */
    protected const FIELD_NOTE = 'note';

    /**
     * @var string
     */
    protected const FIELD_EXTERNAL_IMAGE_URL = 'externalImageUrl';

    /**
     * @var string
     */
    public const FIELD_ASSIGNED_COMPANIES = 'assignedCompanies';

    /**
     * @var string
     */
    protected const LABEL_COMPANY = 'Company';

    /**
     * @var string
     */
    protected const LABEL_BUSINESS_UNIT_OWNER = 'Business unit owner';

    /**
     * @var string
     */
    public const FIELD_IMAGE = 'asset_image';

    /**
     * @var string
     */
    public const OPTION_ORIGINAL_IMAGE_URL = 'imageUrl';

    /**
     * @var string
     */
    public const FIELD_ASSIGNED_BUSINESS_UNITS = 'assignedBusinessUnits';

    /**
     * @var string
     */
    protected const LABEL_BUSINESS_UNIT = 'Business Units';

    /**
     * @uses \Spryker\Zed\CompanyGui\Communication\Controller\SuggestController::indexAction()
     *
     * @var string
     */
    protected const ROUTE_COMPANY_SUGGEST = '/company-gui/suggest';

    /**
     * @uses \Spryker\Zed\CompanyBusinessUnitGui\Communication\Controller\SuggestController::indexAction()
     *
     * @var string
     */
    protected const ROUTE_BUSINESS_UNIT_SUGGEST = '/company-business-unit-gui/suggest?';

    /**
     * @var string
     */
    protected const PLACEHOLDER_SEARCH = 'Start typing to search...';

    /**
     * @var string
     */
    public const OPTION_COMPANY_ASSIGMENT_OPTIONS = 'companyAssignments';

    /**
     * @var string
     */
    public const OPTION_BUSINESS_UNIT_ASSIGMENT_OPTIONS = 'businessUnitAssignments';

    /**
     * @var string
     */
    public const OPTION_BUSINESS_UNIT_OWNER = 'businessUnitOwner';

    /**
     * @var string
     */
    public const OPTION_STATUS_OPTIONS = 'statuses';

    /**
     * @var string
     */
    public const FORM_NAME = 'assetForm';

    public function getBlockPrefix(): string
    {
        return static::FORM_NAME;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            static::OPTION_BUSINESS_UNIT_ASSIGMENT_OPTIONS,
            static::OPTION_BUSINESS_UNIT_OWNER,
            static::OPTION_STATUS_OPTIONS,
            static::OPTION_COMPANY_ASSIGMENT_OPTIONS,
            ]);

        $resolver->setDefaults([
            'data_class' => SspAssetTransfer::class,
            static::OPTION_ORIGINAL_IMAGE_URL => null,
            static::OPTION_BUSINESS_UNIT_ASSIGMENT_OPTIONS => [],
            static::OPTION_COMPANY_ASSIGMENT_OPTIONS => [],
            static::OPTION_STATUS_OPTIONS => [],
            static::OPTION_BUSINESS_UNIT_OWNER => [],
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this
            ->addImageField($builder, $options)
            ->addNameField($builder)
            ->addSerialNumberField($builder)
            ->addStatusField($builder, $options)
            ->addExternalImageUrlField($builder)
            ->addNoteField($builder)
            ->addCompanyField($builder, $options)
            ->addAssignedBusinessUnitField($builder, $options)
            ->addBusinessUnitOwnerField($builder, $options);

        $this->addAssignedBusinessUnitDataListener($builder);
        $this->addBusinessUnitOwnerValidationListener($builder);
        $this->addCompanyAssignmentValidationListener($builder);
        $this->addBusinessUnitAssignmentValidationListener($builder);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    protected function addBusinessUnitOwnerField(FormBuilderInterface $builder, array $options = [])
    {
        $builder->add(static::FIELD_BUSINESS_UNIT_OWNER, Select2ComboBoxType::class, [
            'label' => static::LABEL_BUSINESS_UNIT_OWNER,
            'choices' => $options[static::OPTION_BUSINESS_UNIT_OWNER],
            'multiple' => false,
            'mapped' => true,
            'required' => true,
            'attr' => [
                'data-autocomplete-url' => static::ROUTE_BUSINESS_UNIT_SUGGEST,
                'data-clear-initial' => false,
                'dependent-autocomplete-key' => 'idsCompany',
                'data-depends-on-field' => '.js-select-dependable--assigned-companies',
                'class' => 'js-select-dependable js-select-dependable--business-unit spryker-form-select2combobox',
                'data-minimum-input-length' => 3,
                'placeholder' => static::PLACEHOLDER_SEARCH,
                'data-clearable' => true,
            ],
        ]);

        $builder->get(static::FIELD_BUSINESS_UNIT_OWNER)->addModelTransformer(
            new CallbackTransformer(
                function ($companyBusinessUnitTransfer) {
                    if (!$companyBusinessUnitTransfer instanceof CompanyBusinessUnitTransfer) {
                        return null;
                    }

                    return $companyBusinessUnitTransfer->getIdCompanyBusinessUnit();
                },
                function ($idCompanyBusinessUnitSubmitted) {
                    if (!$idCompanyBusinessUnitSubmitted) {
                        return null;
                    }

                    return (new CompanyBusinessUnitTransfer())->setIdCompanyBusinessUnit($idCompanyBusinessUnitSubmitted);
                },
            ),
        );

        return $this;
    }

    /**
     * @return $this
     */
    protected function addNameField(FormBuilderInterface $builder)
    {
        $builder->add(static::FIELD_NAME, TextType::class, [
            'label' => 'Name',
            'required' => true,
            'sanitize_xss' => true,
            'constraints' => [
                new NotBlank(),
                new Length([
                    'min' => 3,
                    'max' => 255,
                    'minMessage' => 'self_service_portal.asset.form.name.validation.min',
                ]),
            ],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    protected function addSerialNumberField(FormBuilderInterface $builder)
    {
        $builder->add(static::FIELD_SERIAL_NUMBER, TextType::class, [
            'label' => 'Serial number',
            'required' => false,
            'sanitize_xss' => true,
            'constraints' => [
                new Length([
                    'max' => 255,
                    'minMessage' => 'self_service_portal.asset.form.serial_number.validation.min',
                ]),
            ],
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    protected function addStatusField(FormBuilderInterface $builder, array $options = [])
    {
        $builder->add(static::FIELD_STATUS, ChoiceType::class, [
            'label' => 'Status',
            'required' => true,
            'choices' => $options[static::OPTION_STATUS_OPTIONS],
            'multiple' => false,
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    protected function addNoteField(FormBuilderInterface $builder)
    {
        $builder->add(static::FIELD_NOTE, TextareaType::class, [
            'label' => 'Note',
            'required' => false,
            'sanitize_xss' => true,
            'constraints' => [
                new Length([
                    'max' => 1000,
                    'minMessage' => 'self_service_portal.asset.form.note.validation.min',
                ]),
            ],
            'attr' => ['rows' => 5],
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    protected function addImageField(FormBuilderInterface $builder, array $options = [])
    {
        $builder->add(static::FIELD_IMAGE, SspAssetImageForm::class, [
            'mapped' => false,
            SspAssetImageForm::OPTION_ORIGINAL_IMAGE_URL => $options[static::OPTION_ORIGINAL_IMAGE_URL] ?? null,
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    protected function addCompanyField(FormBuilderInterface $builder, array $options = [])
    {
        $builder->add(static::FIELD_ASSIGNED_COMPANIES, Select2ComboBoxType::class, [
            'label' => static::LABEL_COMPANY,
            'choices' => $options[static::OPTION_COMPANY_ASSIGMENT_OPTIONS],
            'multiple' => true,
            'mapped' => false,
            'required' => true,
            'attr' => [
                'data-autocomplete-url' => static::ROUTE_COMPANY_SUGGEST,
                'data-minimum-input-length' => 3,
                'dependent-autocomplete-key' => 'idsCompany',
                'data-dependent-name' => 'idsCompany',
                'placeholder' => static::PLACEHOLDER_SEARCH,
                'data-qa' => 'ssp-asset-assigned-companies-field',
                'class' => 'js-select-dependable--assigned-companies',
            ],
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    protected function addAssignedBusinessUnitField(FormBuilderInterface $builder, array $options = [])
    {
        $builder->add(static::FIELD_ASSIGNED_BUSINESS_UNITS, Select2ComboBoxType::class, [
            'label' => static::LABEL_BUSINESS_UNIT,
            'choices' => $options[static::OPTION_BUSINESS_UNIT_ASSIGMENT_OPTIONS],
            'multiple' => true,
            'required' => true,
            'mapped' => false,
            'attr' => [
                'data-autocomplete-url' => static::ROUTE_BUSINESS_UNIT_SUGGEST,
                'data-clear-initial' => false,
                'dependent-autocomplete-key' => 'idsCompany',
                'data-depends-on-field' => '.js-select-dependable--assigned-companies',
                'class' => 'js-select-dependable js-select-dependable--business-unit spryker-form-select2combobox',
                'data-qa' => 'ssp-asset-business-unit-field',
            ],
        ]);

        return $this;
    }

    protected function addAssignedBusinessUnitDataListener(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event): void {
                $sspAssetTransfer = $event->getData();

                if (!$sspAssetTransfer instanceof SspAssetTransfer) {
                    return;
                }

                if ($event->getForm()->has(static::FIELD_ASSIGNED_BUSINESS_UNITS)) {
                    $event->getForm()->get(static::FIELD_ASSIGNED_BUSINESS_UNITS)->setData($this->extractBusinessUnitsFromTransfer($sspAssetTransfer));
                }
                if ($event->getForm()->has(static::FIELD_ASSIGNED_COMPANIES)) {
                    $event->getForm()->get(static::FIELD_ASSIGNED_COMPANIES)->setData($this->extractCompaniesFromTransfer($sspAssetTransfer));
                }
            },
        );
    }

    /**
     * Adds a validation listener to ensure business unit owner is one of the assigned business units
     */
    protected function addBusinessUnitOwnerValidationListener(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event): void {
                $data = $event->getData();
                $form = $event->getForm();

                $businessUnitOwnerId = $data[static::FIELD_BUSINESS_UNIT_OWNER] ?? null;
                $assignedBusinessUnitIds = $data[static::FIELD_ASSIGNED_BUSINESS_UNITS] ?? [];

                if (!$businessUnitOwnerId && $assignedBusinessUnitIds === []) {
                    return;
                }

                if (!$businessUnitOwnerId && $assignedBusinessUnitIds !== []) {
                    $form->addError(new FormError('Business unit owner is required when business units are assigned.'));

                    return;
                }

                if (!in_array($businessUnitOwnerId, $assignedBusinessUnitIds)) {
                    $form->addError(new FormError('Business unit owner must be one of the assigned business units.'));
                }
            },
        );
    }

    /**
     * Adds a validation listener to ensure assigned companies contain all companies from assigned business units
     */
    protected function addCompanyAssignmentValidationListener(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event): void {
                $this->validateCompanyAssignment($event);
            },
        );
    }

    protected function validateCompanyAssignment(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (!$this->hasFieldValue($data, static::FIELD_ASSIGNED_BUSINESS_UNITS)) {
            return;
        }

        if (!$this->hasFieldValue($data, static::FIELD_ASSIGNED_COMPANIES)) {
            $form->addError(new FormError('Companies must be assigned when business units are assigned.'));

            return;
        }

        $assignedBusinessUnitIds = array_map('intval', $data[static::FIELD_ASSIGNED_BUSINESS_UNITS]);
        $assignedCompanyIds = array_map('intval', $data[static::FIELD_ASSIGNED_COMPANIES]);

        $businessUnitOptions = $form->getConfig()->getOption(static::OPTION_BUSINESS_UNIT_ASSIGMENT_OPTIONS);
        $companyOptions = $form->getConfig()->getOption(static::OPTION_COMPANY_ASSIGMENT_OPTIONS);

        if (!$businessUnitOptions || !$companyOptions) {
            return;
        }

        $requiredCompanyIds = $this->getRequiredCompanyIds($assignedBusinessUnitIds);
        $missingCompanyIds = array_diff($requiredCompanyIds, $assignedCompanyIds);

        if ($missingCompanyIds) {
            $form->addError(new FormError($this->buildMissingCompaniesErrorMessage($missingCompanyIds)));
        }
    }

    /**
     * @param mixed $data
     */
    protected function hasFieldValue($data, string $fieldName): bool
    {
        return isset($data[$fieldName]) && $data[$fieldName];
    }

    /**
     * @param array<int> $assignedBusinessUnitIds
     *
     * @return array<int>
     */
    protected function getRequiredCompanyIds(array $assignedBusinessUnitIds): array
    {
        $businessUnitCollection = $this->getFactory()->getCompanyBusinessUnitFacade()->getCompanyBusinessUnitCollection(
            (new CompanyBusinessUnitCriteriaFilterTransfer())->setCompanyBusinessUnitIds($assignedBusinessUnitIds),
        );

        $requiredCompanyIds = [];
        foreach ($businessUnitCollection->getCompanyBusinessUnits() as $businessUnit) {
            if ($businessUnit->getCompany() && $businessUnit->getCompany()->getIdCompany()) {
                $requiredCompanyIds[] = $businessUnit->getCompany()->getIdCompany();
            }
        }

        return array_unique($requiredCompanyIds);
    }

    /**
     * @param array<int> $missingCompanyIds
     */
    protected function buildMissingCompaniesErrorMessage(array $missingCompanyIds): string
    {
        $missingCompanyCollection = $this->getFactory()->getCompanyFacade()->getCompanyCollection(
            (new CompanyCriteriaFilterTransfer())->setCompanyIds($missingCompanyIds),
        );

        $missingCompanyNames = [];
        foreach ($missingCompanyCollection->getCompanies() as $company) {
            $missingCompanyNames[] = $company->getName();
        }

        return sprintf(
            'All companies that own the assigned business units must be included in assigned companies. Missing companies: %s.',
            implode(', ', $missingCompanyNames),
        );
    }

    /**
     * Adds a validation listener to ensure that if a company is selected, at least one business unit of that company is selected too
     */
    protected function addBusinessUnitAssignmentValidationListener(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event): void {
                $this->validateBusinessUnitAssignment($event);
            },
        );
    }

    protected function validateBusinessUnitAssignment(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (!$this->hasFieldValue($data, static::FIELD_ASSIGNED_COMPANIES)) {
            return;
        }

        if (!$this->hasFieldValue($data, static::FIELD_ASSIGNED_BUSINESS_UNITS)) {
            return;
        }

        $assignedCompanyIds = array_map('intval', $data[static::FIELD_ASSIGNED_COMPANIES]);
        $assignedBusinessUnitIds = array_map('intval', $data[static::FIELD_ASSIGNED_BUSINESS_UNITS]);

        $companiesWithoutBusinessUnits = $this->findCompanyNamesWithoutSelectedBusinessUnit(
            $assignedCompanyIds,
            $assignedBusinessUnitIds,
            $this->groupBusinessUnitIdsByCompanyId($assignedCompanyIds),
        );

        if ((bool)$companiesWithoutBusinessUnits) {
            $errorMessage = sprintf(
                'For each selected company, at least one of its business units must be selected. Missing business units for companies: %s.',
                implode(', ', $companiesWithoutBusinessUnits),
            );

            $form->addError(new FormError($errorMessage));
        }
    }

    /**
     * @param array<int> $assignedCompanyIds
     *
     * @return array<int, array<int>>
     */
    protected function groupBusinessUnitIdsByCompanyId(array $assignedCompanyIds): array
    {
        $businessUnitCollection = $this->getFactory()->getCompanyBusinessUnitFacade()->getCompanyBusinessUnitCollection(
            (new CompanyBusinessUnitCriteriaFilterTransfer())->setCompanyIds($assignedCompanyIds),
        );

        $companyBusinessUnits = [];
        foreach ($businessUnitCollection->getCompanyBusinessUnits() as $businessUnit) {
            if ($businessUnit->getFkCompany() && $businessUnit->getIdCompanyBusinessUnit()) {
                $companyBusinessUnits[$businessUnit->getFkCompany()][] = $businessUnit->getIdCompanyBusinessUnit();
            }
        }

        return $companyBusinessUnits;
    }

    /**
     * @param array<int> $assignedCompanyIds
     * @param array<int> $assignedBusinessUnitIds
     * @param array<int, array<int>> $companyBusinessUnits
     *
     * @return array<string>
     */
    protected function findCompanyNamesWithoutSelectedBusinessUnit(
        array $assignedCompanyIds,
        array $assignedBusinessUnitIds,
        array $companyBusinessUnits
    ): array {
        $companiesWithoutBusinessUnits = [];

        foreach ($assignedCompanyIds as $companyId) {
            if ($this->hasSelectedBusinessUnit($companyBusinessUnits[$companyId] ?? [], $assignedBusinessUnitIds)) {
                continue;
            }

            $companyCollection = $this->getFactory()->getCompanyFacade()->getCompanyCollection(
                (new CompanyCriteriaFilterTransfer())->setCompanyIds([$companyId]),
            );

            if ($companyCollection->getCompanies()->count() > 0) {
                $companiesWithoutBusinessUnits[] = $companyCollection->getCompanies()->getIterator()->current()->getNameOrFail();
            }
        }

        return $companiesWithoutBusinessUnits;
    }

    /**
     * @param array<int> $companyBusinessUnitIds
     * @param array<int> $assignedBusinessUnitIds
     */
    protected function hasSelectedBusinessUnit(array $companyBusinessUnitIds, array $assignedBusinessUnitIds): bool
    {
        foreach ($companyBusinessUnitIds as $businessUnitId) {
            if (in_array($businessUnitId, $assignedBusinessUnitIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int>
     */
    protected function extractBusinessUnitsFromTransfer(SspAssetTransfer $sspAssetTransfer): array
    {
        $sspAssetAssignmentTransfers = $sspAssetTransfer->getBusinessUnitAssignments();
        if ($sspAssetAssignmentTransfers->count() === 0) {
            return [];
        }

        $businessUnitIds = [];
        foreach ($sspAssetAssignmentTransfers as $sspAssetAssignmentTransfer) {
            $companyBusinessUnitTransfer = $sspAssetAssignmentTransfer->getCompanyBusinessUnit();
            if ($companyBusinessUnitTransfer) {
                $businessUnitIds[sprintf(
                    '%s (ID: %s)',
                    $companyBusinessUnitTransfer->getNameOrFail(),
                    $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail(),
                )] = $companyBusinessUnitTransfer->getIdCompanyBusinessUnitOrFail();
            }
        }

        return $businessUnitIds;
    }

    /**
     * @return array<int>
     */
    protected function extractCompaniesFromTransfer(SspAssetTransfer $sspAssetTransfer): array
    {
        $sspAssetAssignmentTransfers = $sspAssetTransfer->getBusinessUnitAssignments();
        if ($sspAssetAssignmentTransfers->count() === 0) {
            return [];
        }

        $companyIds = [];
        foreach ($sspAssetAssignmentTransfers as $sspAssetAssignmentTransfer) {
            $companyBusinessUnitTransfer = $sspAssetAssignmentTransfer->getCompanyBusinessUnit();
            if ($companyBusinessUnitTransfer) {
                $companyTransfer = $companyBusinessUnitTransfer->getCompanyOrFail();
                $companyIds[sprintf(
                    '%s (ID: %s)',
                    $companyTransfer->getNameOrFail(),
                    $companyTransfer->getIdCompanyOrFail(),
                )] = $companyTransfer->getIdCompanyOrFail();
            }
        }

        return $companyIds;
    }

    /**
     * @return $this
     */
    protected function addExternalImageUrlField(FormBuilderInterface $builder)
    {
        $builder->add(static::FIELD_EXTERNAL_IMAGE_URL, UrlType::class, [
            'label' => 'External image URL',
            'required' => false,
            'sanitize_xss' => true,
            'constraints' => [
                new Length([
                    'max' => 2048,
                    'maxMessage' => 'External image URL cannot be longer than {{ limit }} characters.',
                ]),
                new Url([
                    'message' => 'Please enter a valid URL.',
                ]),
            ],
            'attr' => [
                'placeholder' => 'https://example.com/image.jpg',
                'data-qa' => 'ssp-asset-external-image-url-field',
            ],
        ]);

        return $this;
    }
}
