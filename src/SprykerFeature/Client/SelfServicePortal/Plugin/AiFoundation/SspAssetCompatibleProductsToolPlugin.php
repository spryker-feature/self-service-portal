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
use SprykerFeature\Client\SelfServicePortal\Asset\Reader\SspAssetCompatibleProductReader;
use SprykerFeature\Client\SelfServicePortal\Plugin\AiFoundation\Tool\ToolParameter;

/**
 * @method \SprykerFeature\Client\SelfServicePortal\SelfServicePortalFactory getFactory()
 */
class SspAssetCompatibleProductsToolPlugin extends AbstractPlugin implements ToolPluginInterface
{
    public const string TOOL_NAME = 'ssp_asset_compatible_products';

    protected const string TOOL_DESCRIPTION = <<<'DESCRIPTION'
        Searches the catalog for the spare parts or the services that are compatible with a Self-Service Portal asset. It returns the same products as the "Shop Spare Parts" and "Search services" actions of the asset details page.
        - Show its products with `display_products`, by their `idProductAbstract`. For a document question, show only the products the answer is about.
        - `hasMoreResults` true: call it again with the next `page` for more products.
        - `totalResults` 0: follow `suggestedNextAction`. Never replace the result with products of `catalog_search`, because they are not checked for compatibility.
        - Document questions about the asset, such as how to replace a part, how to install a spare part or what a service includes: call it first with a short `query` for the part or topic (or without `query` for a general question), then search the documents of the returned products with `product_knowledge_search`, when available, passing their `sku` values as `abstract_skus`. Answer from these documents only, and name the document. These documents describe the compatible products, not the asset itself: when the document searches with the relevant products give no answer, tell the customer that the documents do not answer the question.
        - A follow-up question about one product of an earlier reply: search only the documents of this product.
        - Without an asset reference from the page context, find the asset with `ssp_asset_search` when the customer names it, otherwise ask the customer to open or name the asset.
        - `isAssetAvailable` false: the customer cannot see the asset. Show no products.
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
                SspAssetCompatibleProductReader::ARGUMENT_ASSET_REFERENCE,
                ToolParameterType::String,
                'The asset reference, such as "AST--3": from the page context of the asset details page, or as the customer names it.',
                true,
            ),
            new ToolParameter(
                SspAssetCompatibleProductReader::ARGUMENT_PRODUCT_KIND,
                ToolParameterType::String,
                sprintf('One of: %s.', implode(', ', SspAssetCompatibleProductReader::SUPPORTED_PRODUCT_KINDS)),
                true,
            ),
            new ToolParameter(
                SspAssetCompatibleProductReader::ARGUMENT_QUERY,
                ToolParameterType::String,
                'Optional. The part or the service in two to four words, such as "ink cartridge". Omit to list all compatible products of the kind.',
                false,
            ),
            new ToolParameter(
                SspAssetCompatibleProductReader::ARGUMENT_PAGE,
                ToolParameterType::Integer,
                'The 1-based page.',
                false,
            ),
            new ToolParameter(
                SspAssetCompatibleProductReader::ARGUMENT_LIMIT,
                ToolParameterType::Integer,
                sprintf(
                    'How many products to return, at most %d. Omit for %d.',
                    SspAssetCompatibleProductReader::LIMIT_MAX,
                    SspAssetCompatibleProductReader::DEFAULT_LIMIT,
                ),
                false,
            ),
        ];
    }

    /**
     * {@inheritDoc}
     * - Checks that the current company user can see the asset, and returns no products otherwise.
     * - Searches the catalog with the product class of the product kind and the asset compatibility filter of the Self-Service Portal.
     * - Requires `\SprykerFeature\Client\SelfServicePortal\Plugin\Catalog\SspAssetQueryExpanderPlugin` in the catalog search query expander plugins: the expander applies the compatibility filter, and without it the result contains every product of the product class.
     * - Returns no products for a `page` beyond the search result window, and a suggested next action instead.
     *
     * @api
     *
     * @param mixed ...$arguments
     *
     * @return array<string, mixed>
     */
    public function execute(...$arguments): mixed
    {
        return $this->getFactory()->createSspAssetCompatibleProductReader()->searchCompatibleProducts($arguments);
    }
}
