<?php

namespace Plaid\Api;

class PayrollIncome extends Api
{
    public function __construct($client)
    {
        parent::__construct($client);
    }

    public function get($user_token)
    {
        return $this->client()->post('/credit/payroll_income/get', [
            "user_token" => $user_token
        ]);
    }

    public function getUserToken($client_user_id)
    {
        return $this->client()->post('/user/create', [
            "client_user_id" => $client_user_id
        ]);
    }

}
