<?php
declare(strict_types=1);

class SearchService
{
    public function __construct(private Meilisearch $meilisearch, private SearchQueryFactory $searchQueryFactory)
    {
    }

    public function find(SearchRequest $request): array
    {
        return $this->meilisearch->search($this->searchQueryFactory->create($request));
    }

}
