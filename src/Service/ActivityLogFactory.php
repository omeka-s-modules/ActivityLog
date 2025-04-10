<?php
namespace ActivityLog\Service;

use Interop\Container\ContainerInterface;
use ActivityLog\ActivityLog;
use Zend\ServiceManager\Factory\FactoryInterface;

class ActivityLogFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ActivityLog($services);
    }
}
