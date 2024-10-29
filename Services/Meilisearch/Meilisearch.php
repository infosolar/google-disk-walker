<?php

declare(strict_types=1);

class Meilisearch
{
    private MeilisearchClient $meilisearch;

    public function __construct()
    {
        $conf = config('meilisearch');
        $this->meilisearch = new MeilisearchClient($conf['host'], $conf['master_key']);
    }

    public function client()
    {
        return $this->meilisearch;
    }

    public function addDocuments(string $index, array $data): void
    {
        $index = $this->meilisearch->index($index);
        $index->addDocuments($data);
    }

    public function dropIndex(string $index): void
    {
        $this->meilisearch->deleteIndex($index);
    }


    public function search(SearchQuery $query): array
    {
        $index = $this->meilisearch->index('files');
        $facetsConfig = config('meilisearch.entities')[File::class];

        $queryData = [
            'filter' => $query->makeFilter(),
            'sort' => $query->sort,
            'page' => $query->page,
            'hitsPerPage' => $query->limit,
            'limit' => $query->limit,
            'facets' => $facetsConfig['facets'],
            'attributesToHighlight' => ['file_name'],
            'highlightPreTag' => '<span class="highlight">',
            'highlightPostTag' => '</span>',
        ];

        $result = $index->search($query->query, $queryData);
        $facets = $this->facets($query, $result->getFacetDistribution());

        return [
            'data' => $result->getHits(),
            'facets' => $facets,
            'total_count' => $result->getTotalHits(),
            'total_pages' => $result->getTotalPages(),
            'distribution' => $result->getFacetDistribution(),
            'page' => $query->page,
        ];
    }

    public function facets(SearchQuery $query, array $additionalSet = []): array
    {
        $facets = [];
        $index = $this->meilisearch->index('files');
        $facetsConfig = config('meilisearch.entities')[File::class];

        collect($facetsConfig['facets'] ?? [])
            ->map(function (string $attribute) use ($index, $query) {
                return [
                    $attribute => $this->facetSearch(clone $query, $attribute, $index),
                ];
            })
            ->values()
            ->each(function ($facetItem) use ($additionalSet, &$facets) {
                $facetName = array_keys($facetItem)[0];

                if (empty($facetItem[$facetName]) && isset($additionalSet[$facetName])) {
                    $facets[$facetName] = $additionalSet[$facetName];
                    return;
                }

                $facets[$facetName] = $facetItem[$facetName];
            })
            ->toArray();
        return $facets;
    }

    public function facetSearch(SearchQuery $query, string $facetName, Indexes $index = null): array
    {
        $facetQuery = (new FacetSearchQuery())
            ->setFacetName($facetName);

        if (!request()->get($facetName)) {
            $facetQuery->setFilter($query->makeFilter($facetName));
        }

        if ($query->query) {
            $facetQuery->setQuery($query->query);
        }

        return $index
            ->facetSearch($facetQuery)
            ->getFacetHits();
    }
}
