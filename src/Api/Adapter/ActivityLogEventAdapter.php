<?php
namespace ActivityLog\Api\Adapter;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Site;
use Omeka\Stdlib\ErrorStore;

class ActivityLogEventAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'activity_log_event';
    }

    public function getRepresentationClass()
    {
        return 'ActivityLog\Api\Representation\ActivityLogEventRepresentation';
    }

    public function getEntityClass()
    {
        return 'ActivityLog\Entity\ActivityLogEvent';
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
    }
}
