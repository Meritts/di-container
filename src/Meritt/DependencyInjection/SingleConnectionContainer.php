<?php
/**
 * This file is part of the meritt dependency injection package.
 *
 * @link https://github.com/Meritts/di-container
 * @copyright Copyright (c) 2013 Meritt Informação Educacional (http://www.meritt.com.br)
 * @license Proprietary
 */

namespace Meritt\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Meritt\Gimme\PackageManager;
use Meritt\Gimme\Parser\ArrayParser;

/**
 * Class SingleConnectionContainer
 *
 * @author Luís Otávio Cobucci Oblonczyk <lcobucci@gmail.com>
 */
class SingleConnectionContainer extends BaseContainer
{
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
                'driver' => $this->getParam('db.driver', 'pdo_mysql'),
                'charset' => 'utf8'
            ],
            $this->get('doctrine.config')
        );
    }

    /**
     * @return Connection
     */
    protected function getDoctrine_ConnectionService()
    {
        return $this->services['doctrine.connection'] = $this->get('doctrine.em')->getConnection();
    }

    /**
     * @return Configuration
     */
    protected function getDoctrine_ConfigService()
    {
        AnnotationRegistry::registerFile(
            $this->getDir('vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php')
        );

        $this->services['doctrine.config'] = $configuration = new Configuration();

        $configuration->setMetadataCacheImpl($this->get('cache.shared'));
        $configuration->setQueryCacheImpl($this->get('cache.shared'));
        $configuration->setResultCacheImpl($this->get('cache.shared'));
        $configuration->setProxyDir($this->getDir($this->getParameter('orm.proxy.dir')));
        $configuration->setProxyNamespace($this->getParameter('orm.proxy.namespace'));
        $configuration->setAutoGenerateProxyClasses($this->isDevelopment());

        $entityDir = $this->getParameter('orm.entity.dir');
        if (is_array($entityDir)) {
            $paths = [];
            foreach ($entityDir as $dir) {
                $paths[] = $this->getDir($dir);
            }
        } else {
            $paths = [ $this->getDir($entityDir) ];
        }
        unset($entityDir);
        unset($dir);

        $configuration->setMetadataDriverImpl(
            new AnnotationDriver(
                new CachedReader(
                    new AnnotationReader(),
                    $this->get('cache.internal')
                ),
                $paths
            )
        );

        return $configuration;
    }
}
