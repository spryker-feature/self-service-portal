<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\SelfServicePortal\Communication\Strategy;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

interface AttachmentScopeStrategyResolverInterface
{
    public function canProcessScope(string $scopeType, FormInterface $form): bool;

    /**
     * @return array<string, mixed>|null
     */
    public function getFormDataForScope(string $scopeType, Request $request): ?array;

    /**
     * @return array<string>
     */
    public function getSupportedScopeTypes(): array;
}
