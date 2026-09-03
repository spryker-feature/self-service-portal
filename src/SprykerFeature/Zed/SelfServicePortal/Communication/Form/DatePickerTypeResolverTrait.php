<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\SelfServicePortal\Communication\Form;

use Spryker\Zed\Gui\Communication\Form\Type\DatePickerType;
use Spryker\Zed\Gui\Communication\Form\Type\DateTimePickerType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;

trait DatePickerTypeResolverTrait
{
    protected const string RANGE_ROLE_START = 'start';

    protected const string RANGE_ROLE_END = 'end';

    protected function getDateFieldType(): string
    {
        if ($this->isGuiDatePickerTypeAvailable()) {
            return DatePickerType::class;
        }

        return DateType::class;
    }

    protected function getDateTimeFieldType(): string
    {
        if ($this->isGuiDateTimePickerTypeAvailable()) {
            return DateTimePickerType::class;
        }

        return DateTimeType::class;
    }

    /**
     * @param array<string, mixed> $legacyAttributes
     *
     * @return array<string, mixed>
     */
    protected function getDateFieldOptions(
        ?string $rangeGroup = null,
        ?string $rangeRole = null,
        array $legacyAttributes = []
    ): array {
        if ($this->isGuiDatePickerTypeAvailable()) {
            if ($rangeGroup === null || $rangeRole === null) {
                return [];
            }

            return [
                'range_group' => $rangeGroup,
                'range_role' => $rangeRole,
            ];
        }

        $options = ['widget' => 'single_text'];

        if ($legacyAttributes !== []) {
            $options['attr'] = $legacyAttributes;
        }

        return $options;
    }

    protected function isGuiDatePickerTypeAvailable(): bool
    {
        return class_exists(DatePickerType::class);
    }

    protected function isGuiDateTimePickerTypeAvailable(): bool
    {
        return class_exists(DateTimePickerType::class);
    }
}
