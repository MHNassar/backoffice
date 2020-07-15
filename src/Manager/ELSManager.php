<?php


namespace App\Manager;


use Elastica\Client;
use Elastica\Index;
use Elastica\Query;
use Elastica\QueryBuilder;
use Elastica\Search;
use Elastica\Status;
use Exception;

class ELSManager
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $indexAlias;

    /**
     * @var Index
     */
    private $index = null;

    public function __construct(string $indexAlias, Client $client)
    {
        $this->client = $client;
        $this->indexAlias = $indexAlias;
    }

    /**
     * @param array $searchFilters
     * @param int $page
     * @return array
     * @throws Exception
     */
    public function search(array $searchFilters, int $page = 1): array
    {
        $search = new Search($this->client);
        $search->addIndex($this->getIndex());

        $pageSize = 14;
        $from = ($page - 1) * $pageSize;

        $query = new Query();
        $query->setSource(['id', 'title', 'vehicleType', 'season', 'category', 'manufacturerCategory', 'manufacturerName', 'status']);
        $query->setFrom($from);
        $query->setSize($pageSize);
        $query->setQuery($this->getQuery($searchFilters));

        $search->setQuery($query);
        $results = $search->search();
        return $results->getResults();
    }

    /**
     * @return Index
     * @throws Exception
     */
    private function getIndex(): Index
    {
        if ($this->index === null) {
            $status = new Status($this->client);
            $indices = $status->getIndicesWithAlias($this->indexAlias);

            if (empty($indices)) {
                throw new Exception(sprintf('Index %s not found', $this->indexAlias));
            }

            $this->index = $indices[0];
        }

        return $this->index;
    }

    private function getQuery(array $filters): Query\AbstractQuery
    {

        $queryBuilder = new QueryBuilder();
        $query = $queryBuilder->query()->bool();
        foreach ($filters as $key => $value) {
            if (!is_null($value) && !empty($value)) {
                $query->addMust($queryBuilder->query()->match($key, $value));
            }
        }
        // dd($query);
        return $query;
    }

}