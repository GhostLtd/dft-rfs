<?php

namespace App\DependencyInjection;

use App\Utility\AuditLogger;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AuditLogPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(AuditLogger::class)) {
            return;
        }

        $definition = $container->findDefinition(AuditLogger::class);

        $auditEntityLoggers = $container->findTaggedServiceIds('app.audit_entity_logger');

        foreach($auditEntityLoggers as $id => $tags) {
            $definition->addMethodCall('addAuditEntityLogger', [new Reference($id)]);
        }
    }
}