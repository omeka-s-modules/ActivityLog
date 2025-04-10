<?php
namespace ActivityLog\Service\Form;

use ActivityLog\Form\EventFilterForm;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class EventFilterFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $eventFilterForm = new EventFilterForm;
        $eventFilterForm->setServices($services);
        return $eventFilterForm;
    }
}
