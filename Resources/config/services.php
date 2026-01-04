<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Hypertext\Bundle\Factory\HypertextFactory;
use Hypertext\Bundle\DependencyInjection\CacheWarmer;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->defaults()
        ->public(false);

    // htaccess.factory
    $services
        ->set('htaccess.factory', HypertextFactory::class)
        ->public(true)
        ->arg(0, service('parameter_bag'));

    // CacheWarmer
    $services
        ->set(CacheWarmer::class)
        ->public(true)
        ->tag('kernel.cache_warmer')
        ->arg(0, service('htaccess.factory'));
};