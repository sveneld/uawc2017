<?php

namespace uawc\Cache;

interface CacheInterface
{
    public function get($key);

    public function save($key, $data, $expire = 0);

    public function delete($key);
}