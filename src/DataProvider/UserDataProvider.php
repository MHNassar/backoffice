<?php


namespace App\DataProvider;


use App\Security\User;

class UserDataProvider extends DataProviderBase
{
    protected $modelName = User::class;

    public function getUserByEmail(string $email)
    {
        $query = <<<SQL
          SELECT * from sysadminuser where sysadminuser_email =:email
SQL;
        $parameters = [
            ':email' => $email
        ];

        $response = $this->dbGenie->doFetchAllAssoc($query, $parameters);
        $results = $this->getOne($response);
        return $this->map($results);
    }

}