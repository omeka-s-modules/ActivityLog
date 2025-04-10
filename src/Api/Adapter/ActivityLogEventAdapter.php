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
        if (isset($query['id']) && is_numeric($query['id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.id',
                $this->createNamedParameter($qb, $query['id'])
            ));
        }
        if (isset($query['user_id']) && is_numeric($query['user_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.user',
                $this->createNamedParameter($qb, $query['user_id'])
            ));
        }
        if (isset($query['user_role']) && '' !== trim($query['user_role'])) {
            $userAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.user', $userAlias, 'WITH', $qb->expr()->eq(
                    "$userAlias.role",
                    $this->createNamedParameter($qb, $query['user_role'])
                )
            );
        }
        if (isset($query['ip']) && '' !== trim($query['ip'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.ip',
                $this->createNamedParameter($qb, $query['ip'])
            ));
        }
        if (isset($query['event']) && '' !== trim($query['event'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.event',
                $this->createNamedParameter($qb, $query['event'])
            ));
        }
        if (isset($query['resource']) && '' !== trim($query['resource'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.resource',
                $this->createNamedParameter($qb, $query['resource'])
            ));
        }
        if (isset($query['resource_id']) && '' !== trim($query['resource_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.resourceIdentifier',
                $this->createNamedParameter($qb, $query['resource_id'])
            ));
        }
        if (isset($query['from']) && preg_match('/\d{4}-\d{2}-\d{2}/', $query['from'])) {
            $settings = $this->getServiceLocator()->get('Omeka\Settings');
            $dateTime = new \DateTime($query['from'], new \DateTimeZone($settings->get('time_zone', 'UTC')));
            $timestamp = $dateTime->getTimestamp();
            $qb->andWhere($qb->expr()->gte(
                'omeka_root.created',
                $this->createNamedParameter($qb, $timestamp)
            ));
        }
        if (isset($query['to']) && preg_match('/\d{4}-\d{2}-\d{2}/', $query['to'])) {
            $settings = $this->getServiceLocator()->get('Omeka\Settings');
            $dateTime = new \DateTime($query['to'], new \DateTimeZone($settings->get('time_zone', 'UTC')));
            $timestamp = $dateTime->getTimestamp();
            $qb->andWhere($qb->expr()->lt(
                'omeka_root.created',
                $this->createNamedParameter($qb, $timestamp)
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
