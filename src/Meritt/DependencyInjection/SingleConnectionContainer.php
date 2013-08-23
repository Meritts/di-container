<?php
/**
 * This file is part of the meritt dependency injection package.
 *
 * @link https://github.com/Meritts/di-container
 * @copyright Copyright (c) 2013 Meritt Informação Educacional (http://www.meritt.com.br)
 * @license Proprietary
 */

namespace Meritt\DependencyInjection;

use Lcobucci\ActionMapper2\DependencyInjection\Container;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\MemcachedCache;
use Meritt\Gimme\Parser\ArrayParser;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\Cache;
use Meritt\Gimme\PackageManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Assetic\Cache\ArrayCache;
use Memcached;

/**
 * Class SingleConnectionContainer
 *
 * @author Luís Otávio Cobucci Oblonczyk <lcobucci@gmail.com>
 */
class SingleConnectionContainer extends Container
{
    /**
     * @return bool
     */
    public function isDevelopment()
    {
        return $this->getParameter('environment') == 'dev';
    }

    /**
     * @return PackageManager
     */
    protected function getAssets_ManagerService()
    {
        $parser = new ArrayParser();

        return $this->services['assets.manager'] = $parser->parse(
            include $this->getDir($this->getParameter('gimme.config')),
            $this->get('cache.shared')
        );
    }

    /**
     * @return Cache
     */
    protected function getCache_InternalService()
    {
        $cache = $this->isDevelopment() ? new ArrayCache() : new ApcCache();

        if ($this->hasParameter('cache.prefix')) {
            $cache->setNamespace($this->getParameter('cache.prefix'));
        }

        $this->services['cache.internal'] = $cache;
    }

    /**
     * @return Cache
     */
    protected function getCache_SharedService()
    {
        $cache = $this->createSharedCache();

        if ($this->hasParameter('cache.prefix')) {
            $cache->setNamespace($this->getParameter('cache.prefix'));
        }

        $this->services['cache.shared'] = $cache;
    }

    /**
     * @return Cache
     */
    protected function createSharedCache()
    {
        if ($this->isDevelopment()) {
            return new ApcCache();
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
     * @return EntityManager
     */
    protected function getDoctrine_EmService()
    {
        return $this->services['doctrine.em'] = EntityManager::create(
            [
                'host' => $this->getParam('db.host', ini_get('mysqli.default_host')),
                'dbname' => $this->getParameter('db.schema'),
                'user' => $this->getParam('db.user', ini_get('mysqli.default_user')),
                'password' => $this->getParam('db.passwd', ini_get('mysqli.default_pw')),
                'driver' => $this->getParam('db.passwd', 'pdo_mysql'),
                'charset' => 'UTF-8'
            ],
            $this->get('doctrine.config')
        );
    }

    /**
     * @return \Doctrine\ORM\Configuration
     */
    protected function getDoctrine_ConfigService()
    {
        $this->services['doctrine.config'] = $instance = new Configuration();

        $instance->setMetadataCacheImpl($this->get('cache.internal'));
        $instance->setQueryCacheImpl($this->get('cache.shared'));
        $instance->setResultCacheImpl($this->get('cache.shared'));
        $instance->setProxyDir($this->getDir($this->getParameter('orm.proxy.dir')));
        $instance->setProxyNamespace($this->getParameter('orm.proxy.namespace'));

        $instance->setAutoGenerateProxyClasses(
            $this->getParameter('environment') == 'dev'
        );

        $instance->setMetadataDriverImpl(
            new AnnotationDriver(
                new CachedReader(
                    new AnnotationReader(),
                    $this->get('cache.internal')
                ),
                (array) $this->getDir($this->getParameter('orm.entity.dir'))
            )
        );

        return $instance;
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
