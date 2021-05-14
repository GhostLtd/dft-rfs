<?php

namespace App;

use App\DependencyInjection\AuditLogPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    protected function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AuditLogPass());
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', \PHP_VERSION_ID < 70400 || $this->debug);
        $container->setParameter('container.dumper.inline_factories', true);
        $confDir = $this->getProjectDir().'/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/{routes}/'.$this->environment.'/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');
    }

    public function getCacheDir()
    {
        if (PreKernelFeatures::isEnabled(Features::GAE_ENVIRONMENT)) {
            return sys_get_temp_dir();
        }
        return parent::getCacheDir();
    }

    public function getLogDir()
    {
        if (PreKernelFeatures::isEnabled(Features::GAE_ENVIRONMENT)) {
            return sys_get_temp_dir();
        }
        return parent::getLogDir();
    }

    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('workflow.security.expression_language')) {
            $definition = $container->findDefinition('workflow.security.expression_language');
            foreach ($container->findTaggedServiceIds('workflow.expression_language_provider') as $id => $attributes) {
                $definition->addMethodCall('registerProvider', [new Reference($id)]);
            }
        }

        if ($container->hasDefinition('validator.expression')) {
            $definition = $container->findDefinition('validator.expression');
            $providers = $container->findTaggedServiceIds('validator.expression_language_provider');

            $expressionLanguage = (new Definition(ExpressionLanguage::class, [
                null, array_map(function($service){return new Reference($service);}, array_keys($providers))
            ]));
            $definition->setArguments([$expressionLanguage]);
        }
    }
}


