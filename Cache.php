<?php

class Cache
{
    private $cache_file;
    function __construct($cache_file_name = "cache.json")
    {
        $this->cache_file = $cache_file_name;
        $this->init();
    }
    function init()
    {
        if (!file_exists($this->cache_file)) {
            file_put_contents($this->cache_file, json_encode([]));
        }
    }
    function get($key)
    {
        $data = json_decode(file_get_contents($this->cache_file), true);
        return array_key_exists($key, $data) ? $data[$key] : null;
    }
    function put($key, $data)
    {
        $cached_data = json_decode(file_get_contents($this->cache_file), true);
        $cached_data[$key] = $data;
        file_put_contents($this->cache_file, json_encode($cached_data, JSON_UNESCAPED_UNICODE));
    }
}
