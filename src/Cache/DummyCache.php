<?php

namespace uawc\Cache;

class DummyCache implements CacheInterface
{

    public function get($key)
    {
        return null;
    }

    public function save($key, $data, $expire = 0)
    {
        return true;
    }

    public function delete($key)
    {
        return true;
    }
}