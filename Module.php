<?php
namespace ActivityLog;

use ActivityLog\Entity\ActivityLogEvent;
use DateTime;
use Doctrine\DBAL\Exception as DbalException;
use Omeka\Module\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Session\Container;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include sprintf('%s/config/module.config.php', __DIR__);
    }

    public function install(ServiceLocatorInterface $services)
    {
        $sql = <<<'SQL'
CREATE TABLE activity_log_event (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, created DOUBLE PRECISION NOT NULL, ip VARCHAR(45) DEFAULT NULL, event VARCHAR(255) NOT NULL, event_data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', resource VARCHAR(255) DEFAULT NULL, resource_id VARCHAR(255) DEFAULT NULL, resource_data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', INDEX IDX_FCC8C64DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE activity_log_event ADD CONSTRAINT FK_FCC8C64DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;
SQL;
        $conn = $services->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec($sql);
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec('DROP TABLE IF EXISTS activity_log_event;');
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        /**
         * Log login events.
         */
        $sharedEventManager->attach(
            '*',
            'user.login',
            function (Event $event) {
                $logEvent = new ActivityLogEvent;
                $logEvent->setEvent('user.login');
                $this->logEvent($logEvent);
            }
        );

        /**
         * Log logout events.
         *
         * Note that pre-4.2.0 versions of Omeka S did not pass the user entity
         * to handlers. In that case, this will record that a logout occurred
         * but will not associate it with a user.
         */
        $sharedEventManager->attach(
            '*',
            'user.logout',
            function (Event $event) {
                $logEvent = new ActivityLogEvent;
                $logEvent->setEvent('user.logout');
                $logEvent->setUser($event->getTarget());
                $this->logEvent($logEvent);
            }
        );

        /**
         * Log API adapter events.
         */
        $eventNames = [
            'api.create.post',
            'api.update.post',
            'api.delete.post',
        ];
        foreach ($eventNames as $eventName) {
            $sharedEventManager->attach(
                '*',
                $eventName,
                function (Event $event) {
                    $request = $event->getParam('request');
                    $response = $event->getParam('response');
                    $flushEntityManager = $request->getOption('flushEntityManager', true);
                    if (!$flushEntityManager) {
                        // Assume this operation is a subrequest of a batch
                        // operation and do not log. All information about this
                        // operation can be inferred by the logged batch event.
                        return;
                    }
                    $entity = $response->getContent();
                    $eventData = [
                        'request_options' => $request->getOption(),
                    ];

                    $logEvent = new ActivityLogEvent;
                    $logEvent->setEvent($event->getName());
                    $logEvent->setEventData($eventData);
                    $logEvent->setResource($request->getResource());
                    $logEvent->setResourceId($entity->getId());
                    $logEvent->setResourceData($request->getContent());
                    $this->logEvent($logEvent);
                }
            );
        }
        /**
         * Log media creation.
         */
        $sharedEventManager->attach(
            'Omeka\Entity\Media',
            'entity.persist.post',
            function (Event $event) {
                $entity = $event->getTarget();

                $logEvent = new ActivityLogEvent;
                $logEvent->setEvent($event->getName());
                $logEvent->setResource($entity::class);
                $logEvent->setResourceId($entity->getId());
                $this->logEvent($logEvent);
            }
        );
        /**
         * Log API adapter batch events.
         */
        $eventNames = [
            'api.batch_create.pre',
            'api.batch_update.pre',
            'api.batch_delete.pre',
        ];
        foreach ($eventNames as $eventName) {
            $sharedEventManager->attach(
                '*',
                $eventName,
                function (Event $event) {
                    $adapter = $event->getTarget();
                    $request = $event->getParam('request');
                    $eventData = [
                        'request_options' => $request->getOption(),
                        'request_ids' => $request->getIds(),
                    ];
                    $resourceData = $adapter->preprocessBatchUpdate([], $request);

                    $logEvent = new ActivityLogEvent;
                    $logEvent->setEvent($event->getName());
                    $logEvent->setEventData($eventData);
                    $logEvent->setResource($request->getResource());
                    $logEvent->setResourceData($resourceData);
                    $this->logEvent($logEvent);
                }
            );
        }
    }

    /**
     * Log an event.
     *
     * Note that, to optimize the process, we use the connection and not the
     * entity manager to persist the event in the database. We only use the
     * ActivityLogEvent entity to pass data into this method efficiently.
     */
    public function logEvent(ActivityLogEvent $logEvent) {
        $services = $this->getServiceLocator();

        // If no user ID is passed, set the ID of the logged in user.
        if (!$logEvent->getUser()) {
            $user = $services->get('Omeka\AuthenticationService')->getIdentity();
            if ($user) {
                $logEvent->setUser($user);
            }
        }

        // Log the event.
        $conn = $services->get('Omeka\Connection');
        try {
            $user = $logEvent->getUser();
            $eventData = $logEvent->getEventData();
            $resourceData = $logEvent->getResourceData();
            $conn->insert('activity_log_event', [
                'created' => microtime(true),
                'user_id' => $user ? $user->getId() : null,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'event' => $logEvent->getEvent(),
                'event_data' => $eventData ? json_encode($eventData) : null,
                'resource' => $logEvent->getResource(),
                'resource_id' => $logEvent->getResourceId(),
                'resource_data' => $resourceData ? json_encode($resourceData) : null,
            ]);
        } catch (DbalException $e) {
            // Catch DBAL exceptions and log them instead of breaking the page.
            $services->get('Omeka\Logger')->warn(sprintf('ActivityLog exception: %s', $e->getMessage()));
        }
    }
}
