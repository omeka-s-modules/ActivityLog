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
CREATE TABLE activity_log_event (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, created DOUBLE PRECISION NOT NULL, ip VARCHAR(45) DEFAULT NULL, event VARCHAR(255) NOT NULL, resource VARCHAR(255) DEFAULT NULL, resource_id VARCHAR(255) DEFAULT NULL, data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', INDEX IDX_FCC8C64DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
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
                $eventEntity = new ActivityLogEvent;
                $eventEntity->setEvent('user.login');

                $activityLog = $this->getServiceLocator()->get('ActivityLog\ActivityLog');
                $activityLog->logEvent($eventEntity);
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
                $eventEntity = new ActivityLogEvent;
                $eventEntity->setEvent('user.logout');
                $eventEntity->setUser($event->getTarget());

                $activityLog = $this->getServiceLocator()->get('ActivityLog\ActivityLog');
                $activityLog->logEvent($eventEntity);

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

                    $eventEntity = new ActivityLogEvent;
                    $eventEntity->setEvent($event->getName());
                    $eventEntity->setResource($request->getResource());
                    $eventEntity->setResourceId($response->getContent()->getId());
                    $eventEntity->setData([
                        'request_options' => $request->getOption(),
                        'request_content' => $request->getContent(),
                    ]);

                    $activityLog = $this->getServiceLocator()->get('ActivityLog\ActivityLog');
                    $activityLog->logEvent($eventEntity);
                }
            );
        }
        /**
         * Log media creation.
         *
         * Note that api.create.post does not trigger during media creation
         * becuase it's a subrequest of item create/update.
         */
        $sharedEventManager->attach(
            'Omeka\Entity\Media',
            'entity.persist.post',
            function (Event $event) {
                $entity = $event->getTarget();

                $eventEntity = new ActivityLogEvent;
                $eventEntity->setEvent($event->getName());
                $eventEntity->setResource($entity::class);
                $eventEntity->setResourceId($entity->getId());

                $activityLog = $this->getServiceLocator()->get('ActivityLog\ActivityLog');
                $activityLog->logEvent($eventEntity);
            }
        );
        /**
         * Log API adapter batch events.
         */
        $eventNames = [
            'api.batch_create.post',
            'api.batch_update.post',
            'api.batch_delete.post',
        ];
        foreach ($eventNames as $eventName) {
            $sharedEventManager->attach(
                '*',
                $eventName,
                function (Event $event) {
                    $adapter = $event->getTarget();
                    $request = $event->getParam('request');

                    $eventEntity = new ActivityLogEvent;
                    $eventEntity->setEvent($event->getName());
                    $eventEntity->setResource($request->getResource());
                    $eventEntity->setData([
                        'request_options' => $request->getOption(),
                        'request_content' => $adapter->preprocessBatchUpdate([], $request),
                        'request_ids' => $request->getIds(),
                    ]);

                    $activityLog = $this->getServiceLocator()->get('ActivityLog\ActivityLog');
                    $activityLog->logEvent($eventEntity);
                }
            );
        }
    }
}
