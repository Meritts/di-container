<?php
/**
 * This file is part of the meritt dependency injection package.
 *
 * @link https://github.com/Meritts/di-container
 * @copyright Copyright (c) 2013 Meritt Informação Educacional (http://www.meritt.com.br)
 * @license Proprietary
 */

namespace Meritt\DependencyInjection;

use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\MemcachedCache;
use Lcobucci\ActionMapper2\DependencyInjection\Container;
use Memcached;

/**
 * @author Luís Otávio Cobucci Oblonczyk <lcobucci@gmail.com>
 */
abstract class BaseContainer extends Container
{
    /**
     * @return CacheProvider
     */
    protected function getCache_InternalService()
    {
        $cache = $this->createInternalCache();
        $this->configureCacheNamespace($cache);

        return $this->services['cache.internal'] = $cache;
    }

    /**
     * @return CacheProvider
     */
    protected function getCache_SharedService()
    {
        $cache = $this->createSharedCache();
        $this->configureCacheNamespace($cache);

        return $this->services['cache.shared'] = $cache;
    }

    protected function configureCacheNamespace(CacheProvider $cache)
    {
        if ($this->hasParameter('cache.prefix')) {
            $cache->setNamespace($this->getParameter('cache.prefix'));
        }
    }

    /**
     * @return CacheProvider
     */
    protected function createInternalCache()
    {
        if (php_sapi_name() == 'cli' || $this->isDevelopment()) {
            return new ArrayCache();
        }

        return new ApcCache();
    }

    /**
     * @return CacheProvider
     */
    protected function createSharedCache()
    {
        if ($this->isDevelopment()) {
            return php_sapi_name() == 'cli' ? new ArrayCache() : new ApcCache();
        }

        $driver = new Memcached();
        $driver->addServer(
            $this->getParam('memcache.host', 'localhost'),
            $this->getParam('memcache.port', 11211),
            $this->getParam('memcache.weight', 1)
        );

        $cache = new MemcachedCache();
        $cache->setMemcached($driver);

        return $cache;
    }

    /**
     * @return bool
     */
    public function isDevelopment()
    {
        return $this->getParameter('environment') == 'dev';
    }

    /**
     * Returns the project root based on script filename
     *
     * @return string
     */
    protected function getBaseDir()
    {
        return $this->getParam('app.basedir') . '/';
    }

    /**
     * Returns the given parameter if exists, else returns the default value
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function getParam($name, $default = null)
    {
        if ($this->hasParameter($name)) {
            return $this->getParameter($name);
        }

        return $default;
    }

    /**
     * Concatenate the given directory to base dir
     *
     * @param string $dir
     * @return string
     */
    protected function getDir($dir)
    {
        return $this->getBaseDir() . $dir;
    }
}
