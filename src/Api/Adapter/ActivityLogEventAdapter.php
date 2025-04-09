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
        if (isset($query['user_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.user',
                $this->createNamedParameter($qb, $query['user_id'])
            ));
        }
        if (isset($query['ip'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.ip',
                $this->createNamedParameter($qb, $query['ip'])
            ));
        }
        if (isset($query['event'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.event',
                $this->createNamedParameter($qb, $query['event'])
            ));
        }
        if (isset($query['resource'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.resource',
                $this->createNamedParameter($qb, $query['resource'])
            ));
        }
        if (isset($query['resource_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.resourceId',
                $this->createNamedParameter($qb, $query['resource_id'])
            ));
        }
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
