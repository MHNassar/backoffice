<?php

namespace App\Manager;

use Elastica\Result;
use Exception;
use Symfony\Component\HttpFoundation\Request;

class ProductsManager
{
    private $ELSManager;

    public function __construct(ELSManager $ELSManager)
    {
        $this->ELSManager = $ELSManager;
    }

    /**
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function getProducts(Request $request)
    {
        $filters = $this->getParams($request);
        $page = $request->query->get('page', 1);
        $results = $this->ELSManager->search($filters,$page);
        $output = [];
        foreach ($results as $result) {
            /**
             * @var $result Result
             */
            $source = $result->getSource();
            array_push($output, $source);
        }
        return $output;
    }

    private function getParams(Request $request)
    {
        return [
            'id' => $request->query->get('id', null),
            'category' => $request->query->get('category', null),
            'season' => $request->query->get('season', null),
            'manufacturerName' => $request->query->get('manufacturerName', null),
            'status' => $request->query->get('status', null),
        ];
    }
}