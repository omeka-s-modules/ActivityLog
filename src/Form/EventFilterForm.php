<?php
namespace ActivityLog\Form;

use Laminas\Form\Element as LaminasElement;
use Laminas\Form\Form;
use Laminas\ServiceManager\ServiceLocatorInterface;

class EventFilterForm extends Form
{
    protected $services;

    public function init()
    {
        $this->setAttribute('method', 'GET');
        $this->add([
            'type' => LaminasElement\Text::class,
            'name' => 'id',
            'attributes' => [
                'placeholder' => 'Enter an ID', // @translate
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Select::class,
            'name' => 'user_id',
            'options' => [
                'empty_option' => 'Select a user', // @translate
                'value_options' => $this->getUserValueOptions(),
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Select::class,
            'name' => 'user_role',
            'options' => [
                'empty_option' => 'Select a user role', // @translate
                'value_options' => $this->getUserRoleValueOptions(),
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Text::class,
            'name' => 'ip',
            'attributes' => [
                'placeholder' => 'Enter an IP', // @translate
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Select::class,
            'name' => 'event',
            'options' => [
                'empty_option' => 'Select an event', // @translate
                'value_options' => $this->getEventValueOptions(),
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Select::class,
            'name' => 'resource',
            'options' => [
                'empty_option' => 'Select a resource', // @translate
                'value_options' => $this->getResourceValueOptions(),
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Text::class,
            'name' => 'resource_id',
            'attributes' => [
                'placeholder' => 'Enter a resource ID', // @translate
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Text::class,
            'name' => 'from',
            'attributes' => [
                'placeholder' => 'From: yyyy-mm-dd', // @translate
            ],
        ]);
        $this->add([
            'type' => LaminasElement\Text::class,
            'name' => 'before',
            'attributes' => [
                'placeholder' => 'Before: yyyy-mm-dd', // @translate
            ],
        ]);
    }

    /**
     * Get value options for user select.
     */
    public function getUserValueOptions(): array
    {
        $conn = $this->services->get('Omeka\Connection');
        $sql = 'SELECT user.id, user.name, COUNT(activity_log_event.user_id) count
            FROM activity_log_event
            INNER JOIN user ON activity_log_event.user_id = user.id
            GROUP BY activity_log_event.user_id
            ORDER BY user.name';
        $resultSet = $conn->executeQuery($sql);
        $valueOptions = [];
        foreach ($resultSet->fetchAllAssociative() as $user) {
            $valueOptions[$user['id']] = sprintf('%s (%s)', $user['name'], $user['count']);
        }
        return $valueOptions;
    }

    /**
     * Get value options for user role select.
     */
    public function getUserRoleValueOptions(): array
    {
        $conn = $this->services->get('Omeka\Connection');
        $sql = 'SELECT user.role, COUNT(user.role) count
            FROM activity_log_event
            INNER JOIN user ON activity_log_event.user_id = user.id
            GROUP BY user.role
            ORDER by user.role';
        $resultSet = $conn->executeQuery($sql);
        $valueOptions = [];
        foreach ($resultSet->fetchAllAssociative() as $user) {
            $valueOptions[$user['role']] = sprintf('%s (%s)', $user['role'], $user['count']);
        }
        return $valueOptions;
    }

    /**
     * Get value options for event select.
     */
    public function getEventValueOptions(): array
    {
        $conn = $this->services->get('Omeka\Connection');
        $sql = 'SELECT event, COUNT(event) count
            FROM activity_log_event
            GROUP BY event
            ORDER BY event';
        $resultSet = $conn->executeQuery($sql);
        $valueOptions = [];
        foreach ($resultSet->fetchAllAssociative() as $event) {
            $valueOptions[$event['event']] = sprintf('%s (%s)', $event['event'], $event['count']);
        }
        return $valueOptions;
    }

    /**
     * Get value options for resource select.
     */
    public function getResourceValueOptions(): array
    {
        $conn = $this->services->get('Omeka\Connection');
        $sql = 'SELECT resource, COUNT(resource) count
            FROM activity_log_event
            GROUP BY resource
            ORDER BY resource';
        $resultSet = $conn->executeQuery($sql);
        $valueOptions = [];
        foreach ($resultSet->fetchAllAssociative() as $resource) {
            $valueOptions[$resource['resource']] = sprintf('%s (%s)', $resource['resource'], $resource['count']);
        }
        return $valueOptions;
    }

    public function setServices(ServiceLocatorInterface $services): void
    {
        $this->services = $services;
    }
}
