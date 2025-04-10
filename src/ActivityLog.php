<?php
namespace ActivityLog;

use ActivityLog\Entity\ActivityLogEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ActivityLog
{
    protected $services;

    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
    }

    /**
     * Log an event.
     *
     * Note that, to optimize the INSERT query, we use the connection instead of
     * the entity manager to persist the event. We only use the ActivityLogEvent
     * entity to efficiently pass data into this method.
     */
    public function logEvent(ActivityLogEvent $eventEntity): void
    {
        // If no user ID is passed, set the ID of the logged in user.
        if (!$eventEntity->getUser()) {
            $user = $this->services->get('Omeka\AuthenticationService')->getIdentity();
            if ($user) {
                $eventEntity->setUser($user);
            }
        }

        // Log the event.
        $conn = $this->services->get('Omeka\Connection');
        try {
            $user = $eventEntity->getUser();
            $data = $eventEntity->getData();
            $conn->insert('activity_log_event', [
                'created' => microtime(true),
                'user_id' => $user ? $user->getId() : null,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'event' => $eventEntity->getEvent(),
                'resource' => $eventEntity->getResource(),
                'resource_id' => $eventEntity->getResourceId(),
                'data' => $data ? json_encode($data) : null,
            ]);
        } catch (DbalException $e) {
            // Catch DBAL exceptions and log them instead of breaking the page.
            $this->services->get('Omeka\Logger')->warn(sprintf('ActivityLog exception: %s', $e->getMessage()));
        }
    }
}
