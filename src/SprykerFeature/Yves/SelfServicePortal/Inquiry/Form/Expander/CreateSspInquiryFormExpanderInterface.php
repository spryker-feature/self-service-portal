<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\SelfServicePortal\Inquiry\Form\Expander;

use Symfony\Component\Form\FormBuilderInterface;

interface CreateSspInquiryFormExpanderInterface
{
    public function isApplicable(): bool;

    /**
     * @param array<mixed> $options
     */
    public function expand(FormBuilderInterface $builder, array $options): void;
}
