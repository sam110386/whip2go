<?php

namespace Pubnub;

use Pubnub\Pubnub\Pubnub;

/**
 * Pubnub Vendor class, adapted for Laravel/Composer PSR-4 autoloading.
 */
class Pubnubpush
{
    private $pubnubobj;

    public function __construct($pub_key, $sub_key, $secret)
    {
        $this->pubnubobj = new Pubnub([
            'subscribe_key' => $sub_key,
            'publish_key' => $pub_key,
            'secret_key' => $secret,
            'ssl' => false
        ]);
    }

    public function pubnubpublish($user_id, $pushdata)
    {
        if (empty($user_id)) {
            return null;
        }

        $pushdata = array_map(function($v) {
            return (is_null($v)) ? "" : $v;
        }, $pushdata);
        
        return $this->pubnubobj->publish('dia_' . $user_id, $pushdata);
    }
}
