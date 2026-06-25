<?php

namespace Plaid\Api;

class Categories extends Api
{
    public function get()
    {
        return $this->client()->postPublic('/categories/get', new \ArrayObject());
    }
}
