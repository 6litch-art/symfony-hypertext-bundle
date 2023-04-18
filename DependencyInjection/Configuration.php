<?php

namespace Hypertext\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class Configuration implements ConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $this->treeBuilder = new TreeBuilder('hypertext');

        $rootNode = $this->treeBuilder->getRootNode();
        $this->addGlobalOptionsSection($rootNode);

        return $this->treeBuilder;
    }

    private $treeBuilder;
    public function getTreeBuilder(): TreeBuilder
    {
        return $this->treeBuilder;
    }

    private function addGlobalOptionsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()

                ->booleanNode('enable')
                    ->info('Enable feature')
                    ->defaultValue(true)
                    ->end()
                ->scalarNode('encrypt')
                    ->info('Encryption type')
                    ->defaultValue("md5-apr")
                    ->end()

                ->arrayNode('password')
                    ->info('Hypertext password files')
                    ->arrayPrototype()    
                        ->scalarPrototype()->end()
                    ->end()
                ->end()

                ->arrayNode('access')->addDefaultsIfNotSet()
                    ->children()

                    ->arrayNode('error_document')
                        ->scalarPrototype()->end()
                        ->end()
                   
                    ->scalarNode('auth_name')
                        ->defaultValue("Dialog prompt")
                        ->end()

                    ->scalarNode('auth_type')
                        ->defaultValue("Basic")
                        ->end()

                    ->scalarNode('auth_user_file')
                        ->defaultValue(null)
                        ->end()

                    ->arrayNode('files')
                        ->arrayPrototype()
                            ->info("File list")
                            ->children()

                            ->scalarNode('name')
                                ->defaultValue(null)
                                ->end()

                                ->scalarNode('auth_name')
                                ->defaultValue("Dialog prompt")
                                ->end()
        
                            ->scalarNode('auth_type')
                                ->defaultValue("Basic")
                                ->end()
        
                            ->scalarNode('auth_user_file')
                                ->defaultValue(null)
                                ->end()
                            ->end()
                        ->end()
                    ->end()

                    ->arrayNode('files_match')
                        ->arrayPrototype()
                            ->info("File list")
                            ->children()

                            ->scalarNode('pattern')
                                ->defaultValue(null)
                                ->end()

                                ->scalarNode('auth_name')
                                ->defaultValue("Dialog prompt")
                                ->end()
        
                            ->scalarNode('auth_type')
                                ->defaultValue("Basic")
                                ->end()
        
                            ->scalarNode('auth_user_file')
                                ->defaultValue(null)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }
}
