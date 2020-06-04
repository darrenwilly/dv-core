<?php
declare(strict_types=1);

namespace DV\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use \Scienta\DoctrineJsonFunctions\Query\AST\Functions\Mysql as DqlFunction ;

/**
 * This is the class that loads and manages DVDoctrineBundle configuration.
 *
 * @author DarrenTrojan <darren.willy@gmail.com>
 */
class DVExtension extends Extension implements PrependExtensionInterface
{

    public function prepend(ContainerBuilder $container): void
    {
        /**
         * make sure atleast one of the SCIENTA library class exist
         */
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        try {
            ##
            $locator = new FileLocator(dirname(dirname(__DIR__)) . '/config');
            ##
            $loader = new PhpFileLoader($container, $locator);
            ##
            $loader->load('services.php' , 'php');

        }
        catch (\Throwable $exception)   {
           # var_dump($exception->getMessage() . '<br>'. $exception->getTraceAsString()); exit;
        }
    }

}