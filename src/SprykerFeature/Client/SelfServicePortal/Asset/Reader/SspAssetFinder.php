<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Client\SelfServicePortal\Asset\Reader;

use Generated\Shared\Transfer\CompanyUserTransfer;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\SspAssetCollectionTransfer;
use Generated\Shared\Transfer\SspAssetConditionsTransfer;
use Generated\Shared\Transfer\SspAssetCriteriaTransfer;
use Spryker\Client\CompanyUser\CompanyUserClientInterface;
use SprykerFeature\Client\SelfServicePortal\SelfServicePortalConfig;
use SprykerFeature\Client\SelfServicePortal\Zed\SelfServicePortalStubInterface;

class SspAssetFinder implements SspAssetFinderInterface
{
    public const string ARGUMENT_QUERY = 'query';

    public const string ARGUMENT_LIMIT = 'limit';

    public const int DEFAULT_LIMIT = 5;

    public const int LIMIT_MAX = 10;

    public const int QUERY_LENGTH_MAX = 255;

    public const int FALLBACK_WORD_COUNT_MAX = 3;

    public const int FALLBACK_WORD_LENGTH_MIN = 2;

    protected const string KEY_QUERY = 'query';

    protected const string KEY_IS_AVAILABLE = 'isAvailable';

    protected const string KEY_TOTAL_RESULTS = 'totalResults';

    protected const string KEY_ASSETS = 'assets';

    protected const string KEY_SUGGESTED_NEXT_ACTION = 'suggestedNextAction';

    protected const string KEY_REFERENCE = 'reference';

    protected const string KEY_NAME = 'name';

    protected const string KEY_SERIAL_NUMBER = 'serialNumber';

    protected const string KEY_STATUS = 'status';

    protected const string KEY_MATCHED_WORDS = 'matchedWords';

    protected const string WORD_SEPARATOR_PATTERN = '/[\s,;]+/u';

    protected const string NEXT_ACTION_MISSING_QUERY = 'Pass the asset name, a part of it, the serial number or the reference that the customer gives as `query`.';

    protected const string NEXT_ACTION_NO_COMPANY_USER = 'The customer has no company account, so there are no assets. Say so in one short sentence, then search the catalog for the part they named, and say that these products are not checked for compatibility with their equipment.';

    protected const string NEXT_ACTION_NO_MATCH = 'No asset of the customer matches "%s", also not one of its words. Tell the customer that no asset matches, show no products, and ask for the asset name, serial number or reference as the "My Assets" page shows it.';

    protected const string NOTE_MATCHED_WORDS = ' No asset matches the whole query, so these assets match one of its words: confirm the asset with the customer before you show products.';

    protected const string NEXT_ACTION_ONE_MATCH = 'One asset matches. Use its `reference` as `assetReference` in the other asset tools, and name the asset in the reply.';

    protected const string NEXT_ACTION_MORE_MATCHES = '%d assets match. Show no products yet: list the matching assets with name and serial number, and ask the customer which one they mean.';

    protected const string NOTE_MORE_ASSETS = ' Only the first %d are listed: ask the customer for a more specific name or the serial number.';

    public function __construct(
        protected CompanyUserClientInterface $companyUserClient,
        protected SelfServicePortalStubInterface $selfServicePortalStub,
        protected SelfServicePortalConfig $selfServicePortalConfig
    ) {
    }

    /**
     * @param array<int|string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    public function findAssets(array $arguments): array
    {
        if (array_key_exists(0, $arguments) && is_array($arguments[0])) {
            $arguments = $arguments[0];
        }

        $query = $this->extractQuery($arguments);

        if ($query === '') {
            return $this->createEmptyResult($query, static::NEXT_ACTION_MISSING_QUERY);
        }

        $companyUserTransfer = $this->companyUserClient->findCompanyUser();

        if ($companyUserTransfer === null) {
            return $this->createEmptyResult($query, static::NEXT_ACTION_NO_COMPANY_USER);
        }

        $limit = $this->extractLimit($arguments);
        $sspAssetCollectionTransfer = $this->searchAssets($query, $companyUserTransfer, $limit);
        $assets = $this->mapAssets($sspAssetCollectionTransfer);
        $totalResults = max(count($assets), (int)$sspAssetCollectionTransfer->getPagination()?->getNbResults());

        if ($totalResults === 0) {
            return $this->findAssetsByWords($query, $companyUserTransfer, $limit);
        }

        return $this->createResult($query, $assets, $totalResults);
    }

    /**
     * @return array<string, mixed>
     */
    protected function findAssetsByWords(string $query, CompanyUserTransfer $companyUserTransfer, int $limit): array
    {
        $words = $this->extractFallbackWords($query);
        $assets = [];
        $matchedWords = [];

        foreach ($words as $word) {
            $wordAssets = $this->mapAssets($this->searchAssets($word, $companyUserTransfer, $limit));

            if ($wordAssets !== []) {
                $matchedWords[] = $word;
            }

            foreach ($wordAssets as $asset) {
                $assets[(string)$asset[static::KEY_REFERENCE]] = $asset;
            }
        }

        $assets = array_slice(array_values($assets), 0, $limit);

        if ($assets === []) {
            return $this->createResult($query, [], 0);
        }

        $result = $this->createResult($query, $assets, count($assets));
        $result[static::KEY_MATCHED_WORDS] = $matchedWords;
        $result[static::KEY_SUGGESTED_NEXT_ACTION] .= static::NOTE_MATCHED_WORDS;

        return $result;
    }

    /**
     * @return list<string>
     */
    protected function extractFallbackWords(string $query): array
    {
        $words = preg_split(static::WORD_SEPARATOR_PATTERN, $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) < 2) {
            return [];
        }

        $words = array_values(array_unique(array_filter(
            $words,
            static fn (string $word): bool => mb_strlen($word) >= static::FALLBACK_WORD_LENGTH_MIN,
        )));
        usort($words, static fn (string $left, string $right): int => mb_strlen($right) <=> mb_strlen($left));

        return array_slice($words, 0, static::FALLBACK_WORD_COUNT_MAX);
    }

    protected function searchAssets(string $searchText, CompanyUserTransfer $companyUserTransfer, int $limit): SspAssetCollectionTransfer
    {
        $sspAssetCriteriaTransfer = (new SspAssetCriteriaTransfer())
            ->setCompanyUser($companyUserTransfer)
            ->setSspAssetConditions(
                (new SspAssetConditionsTransfer())
                    ->setSearchText($searchText)
                    ->setStatuses($this->selfServicePortalConfig->getAssetDetailsViewStatuses()),
            )
            ->setPagination((new PaginationTransfer())->setPage(1)->setMaxPerPage($limit));

        return $this->selfServicePortalStub->getSspAssetCollection($sspAssetCriteriaTransfer);
    }

    /**
     * @param list<array<string, string|null>> $assets
     *
     * @return array<string, mixed>
     */
    protected function createResult(string $query, array $assets, int $totalResults): array
    {
        return [
            static::KEY_QUERY => $query,
            static::KEY_IS_AVAILABLE => true,
            static::KEY_TOTAL_RESULTS => $totalResults,
            static::KEY_ASSETS => $assets,
            static::KEY_SUGGESTED_NEXT_ACTION => $this->resolveSuggestedNextAction($query, $totalResults, count($assets)),
        ];
    }

    /**
     * @return list<array<string, string|null>>
     */
    protected function mapAssets(SspAssetCollectionTransfer $sspAssetCollectionTransfer): array
    {
        $statusNames = $this->selfServicePortalConfig->getAssetDetailsStatusNames();
        $assets = [];

        foreach ($sspAssetCollectionTransfer->getSspAssets() as $sspAssetTransfer) {
            $assets[] = [
                static::KEY_REFERENCE => $sspAssetTransfer->getReference(),
                static::KEY_NAME => $sspAssetTransfer->getName(),
                static::KEY_SERIAL_NUMBER => $sspAssetTransfer->getSerialNumber(),
                static::KEY_STATUS => $statusNames[(string)$sspAssetTransfer->getStatus()] ?? $sspAssetTransfer->getStatus(),
            ];
        }

        return $assets;
    }

    protected function resolveSuggestedNextAction(string $query, int $totalResults, int $listedCount): string
    {
        if ($totalResults === 0) {
            return sprintf(static::NEXT_ACTION_NO_MATCH, $query);
        }

        if ($totalResults === 1) {
            return static::NEXT_ACTION_ONE_MATCH;
        }

        if ($totalResults > $listedCount) {
            return sprintf(static::NEXT_ACTION_MORE_MATCHES, $totalResults) . sprintf(static::NOTE_MORE_ASSETS, $listedCount);
        }

        return sprintf(static::NEXT_ACTION_MORE_MATCHES, $totalResults);
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function extractQuery(array $arguments): string
    {
        $query = $arguments[static::ARGUMENT_QUERY] ?? null;

        if (!is_string($query)) {
            return '';
        }

        return mb_substr(trim($query), 0, static::QUERY_LENGTH_MAX);
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    protected function extractLimit(array $arguments): int
    {
        $limit = $arguments[static::ARGUMENT_LIMIT] ?? null;

        if (!is_numeric($limit) || (int)$limit < 1) {
            return static::DEFAULT_LIMIT;
        }

        return min((int)$limit, static::LIMIT_MAX);
    }

    /**
     * @return array<string, mixed>
     */
    protected function createEmptyResult(string $query, string $suggestedNextAction): array
    {
        return [
            static::KEY_QUERY => $query,
            static::KEY_IS_AVAILABLE => false,
            static::KEY_TOTAL_RESULTS => 0,
            static::KEY_ASSETS => [],
            static::KEY_SUGGESTED_NEXT_ACTION => $suggestedNextAction,
        ];
    }
}
