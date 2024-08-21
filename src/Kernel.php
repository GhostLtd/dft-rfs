<?php

namespace App;

use App\DependencyInjection\AuditLogPass;
use App\ML\FileTypeMatcher;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    private const string CONFIG_EXTS = '.{php,xml,yaml,yml}';

    #[\Override]
    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    #[\Override]
    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    #[\Override]
    protected function build(ContainerBuilder $container): void
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

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/{routes}/'.$this->environment.'/*'.self::CONFIG_EXTS);
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS);
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS);
    }

    #[\Override]
    public function getCacheDir(): string
    {
        if (PreKernelFeatures::isEnabled(Features::GAE_ENVIRONMENT)) {
            return sys_get_temp_dir();
        }
        return parent::getCacheDir();
    }

    #[\Override]
    public function getLogDir(): string
    {
        if (PreKernelFeatures::isEnabled(Features::GAE_ENVIRONMENT)) {
            return sys_get_temp_dir();
        }
        return parent::getLogDir();
    }

    #[\Override]
    public function process(ContainerBuilder $container): void
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
                null, array_map(fn($service) => new Reference($service), array_keys($providers))
            ]));
            $definition->setArguments([$expressionLanguage]);
        }

        $fileTypeMatcher = $container->getDefinition(FileTypeMatcher::class);
        foreach($container->findTaggedServiceIds('app.ml.file_type') as $id => $tags) {
            $fileTypeMatcher->addMethodCall('addFileType', [new Reference($id)]);
        }
    }
}
