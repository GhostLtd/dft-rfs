<?php

namespace App;

use App\DependencyInjection\AuditLogPass;
use App\ML\FileTypeMatcher;
use Ghost\GovUkCoreBundle\Kernel as CoreKernel;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends CoreKernel implements CompilerPassInterface
{
    // MicroKernelTrait is already used by the parent CoreKernel.

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

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/{routes}/'.$this->environment.'/*'.self::CONFIG_EXTS);
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS);
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS);
    }

    #[\Override]
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new AuditLogPass());
    }

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        parent::process($container);

        // RFS-specific: tag-based ml file-type matcher registration.
        $fileTypeMatcher = $container->getDefinition(FileTypeMatcher::class);
        foreach ($container->findTaggedServiceIds('app.ml.file_type') as $id => $tags) {
            $fileTypeMatcher->addMethodCall('addFileType', [new Reference($id)]);
        }
    }
}
