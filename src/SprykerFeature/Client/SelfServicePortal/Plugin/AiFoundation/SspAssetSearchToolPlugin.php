<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\SelfServicePortal\Plugin\AiFoundation;

use Spryker\Client\AiFoundationExtension\Dependency\Tools\ToolPluginInterface;
use Spryker\Client\Kernel\AbstractPlugin;
use Spryker\Shared\AiFoundationExtension\Tools\ToolParameterType;
use SprykerFeature\Client\SelfServicePortal\Asset\Reader\SspAssetFinder;
use SprykerFeature\Client\SelfServicePortal\Plugin\AiFoundation\Tool\ToolParameter;

/**
 * @method \SprykerFeature\Client\SelfServicePortal\SelfServicePortalFactory getFactory()
 */
class SspAssetSearchToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'ssp_asset_search';

    protected const string TOOL_DESCRIPTION = <<<'DESCRIPTION'
        Finds the Self-Service Portal assets of the customer by name, a part of the name, serial number or reference. Returns only the assets that the customer can see on the "My Assets" page: reference, name, serial number and status.
        - The customer's own equipment is an asset: "my printer", "our copier", "the truck", "my OfficeJet", "CN1234ABCD". When the page context gives no asset, call this tool first, before any catalog search, for spare parts, services, status or document questions about such equipment. Do not use it when the page context already gives the asset.
        - Pass the distinctive part only, for example "OfficeJet", "Titan" or the serial number, not words such as "my". When the whole query matches no asset, the tool searches each of its words and returns `matchedWords`: confirm the asset with the customer then.
        - Never answer a question about the customer's own equipment with `catalog_search` products: they are not checked for compatibility with the asset. Exception: when the tool reports that the customer has no company account, follow its `suggestedNextAction`.
        - One match: use its `reference` as `assetReference` in the other asset tools. More than one match: show no products, list the matches and ask which asset the customer means. No match: tell the customer so and show no products.
        DESCRIPTION;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): string
    {
        return static::TOOL_NAME;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDescription(): string
    {
        return static::TOOL_DESCRIPTION;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<\Spryker\Client\AiFoundationExtension\Dependency\Tools\ToolParameterInterface>
     */
    public function getParameters(): array
    {
        return [
            new ToolParameter(
                SspAssetFinder::ARGUMENT_QUERY,
                ToolParameterType::String,
                'The asset name or a distinctive part of it, the serial number or the reference, such as "OfficeJet", "CN1234ABCD" or "AST--3".',
                true,
            ),
            new ToolParameter(
                SspAssetFinder::ARGUMENT_LIMIT,
                ToolParameterType::Integer,
                sprintf('The maximum number of assets to return. Default %d, maximum %d.', SspAssetFinder::DEFAULT_LIMIT, SspAssetFinder::LIMIT_MAX),
                false,
            ),
        ];
    }

    /**
     * {@inheritDoc}
     * - Requires a logged-in company user, otherwise returns no assets.
     * - Searches the name, the serial number and the reference of the assets with the same permission rules and statuses as the "My Assets" page.
     * - When the whole query matches no asset, searches each of the longest words of the query and returns the assets that match one of them.
     *
     * @api
     *
     * @param mixed ...$arguments
     *
     * @return array<string, mixed>
     */
    public function execute(...$arguments): mixed
    {
        return $this->getFactory()->createSspAssetFinder()->findAssets($arguments);
    }
}
