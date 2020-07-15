<?php

namespace App\DataProvider;

use App\DB\DbGenie;

abstract class DataProviderBase
{
    protected $dbGenie;
    protected $modelName;

    public function __construct(DbGenie $dbGenie)
    {
        $this->dbGenie = $dbGenie;
    }

    protected function getOne(array $results)
    {
        return count($results) > 0 ? $results[0] : null;
    }

    protected function map(array $result)
    {
        $user = new $this->modelName();
        $mapDatabase = $user->dbMap;

        foreach ($mapDatabase as $key => $value) {
            $user->$value($result[$key]);
        }
        return $user;
    }

}