<?php

namespace rockunit\common;


use rock\cache\Memcached;
use rock\cache\Redis;
use rock\helpers\FileHelper;

trait CommonTestTrait
{
    protected static function clearRuntime()
    {
        $runtime = ROCKUNIT_RUNTIME;
        FileHelper::deleteDirectory($runtime);
    }

    protected static function sort($value)
    {
        ksort($value);
        return $value;
    }


    /**
     * @param array $config
     * @return \rock\cache\CacheInterface
     */
    protected static function getCache(array $config = [])
    {
        return new Redis($config);
    }

    protected static function clearCache()
    {
        static::getCache()->flush();
    }
} 