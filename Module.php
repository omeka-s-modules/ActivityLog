<?php
namespace ActivityLog;

use ActivityLog\Entity\ActivityLogEvent;
use Omeka\Module\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include sprintf('%s/config/module.config.php', __DIR__);
    }

    public function install(ServiceLocatorInterface $services)
    {
        $sql = <<<'SQL'
CREATE TABLE activity_log_event (id INT UNSIGNED AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, timestamp DOUBLE PRECISION NOT NULL, ip VARCHAR(45) DEFAULT NULL, event VARCHAR(255) NOT NULL, resource VARCHAR(255) DEFAULT NULL, resource_identifier VARCHAR(255) DEFAULT NULL, data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', INDEX IDX_FCC8C64DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
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
        /*
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

        /*
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
                    $eventEntity->setResourceIdentifier($response->getContent()->getId() ?? $request->getId());
                    $eventEntity->setData([
                        'request_options' => $request->getOption(),
                        'request_content' => $request->getContent(),
                    ]);

                    $activityLog = $this->getServiceLocator()->get('ActivityLog\ActivityLog');
                    $activityLog->logEvent($eventEntity);
                }
            );
        }

        /*
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
                $eventEntity->setResourceIdentifier($entity->getId());

                $activityLog = $this->getServiceLocator()->get('ActivityLog\ActivityLog');
                $activityLog->logEvent($eventEntity);
            }
        );

        /*
         * Log user update.
         *
         * Note that api.update.post contains limited data about a media update.
         * This way we can log password changes, and any other changes that
         * occur.
         */
        $sharedEventManager->attach(
            'Omeka\Entity\User',
            'entity.update.post',
            function (Event $event) {
                $entity = $event->getTarget();
                $args = $event->getParam('LifecycleEventArgs');

                $eventEntity = new ActivityLogEvent;
                $eventEntity->setEvent($event->getName());
                $eventEntity->setResource($entity::class);
                $eventEntity->setResourceIdentifier($entity->getId());
                $eventEntity->setData([
                    'entity_changeset' => $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity),
                ]);

                $activityLog = $this->getServiceLocator()->get('ActivityLog\ActivityLog');
                $activityLog->logEvent($eventEntity);
            }
        );

        /*
         * Log API key creation.
         *
         * Note that API key creation is done entirely within the entity
         * manager.
         */
        $sharedEventManager->attach(
            'Omeka\Entity\ApiKey',
            'entity.persist.post',
            function (Event $event) {
                $entity = $event->getTarget();
                $args = $event->getParam('LifecycleEventArgs');

                $eventEntity = new ActivityLogEvent;
                $eventEntity->setEvent($event->getName());
                $eventEntity->setResource($entity::class);
                $eventEntity->setResourceIdentifier($entity->getId());

                $activityLog = $this->getServiceLocator()->get('ActivityLog\ActivityLog');
                $activityLog->logEvent($eventEntity);
            }
        );

        /*
         * Log API key deletion.
         *
         * Note that API key deletion is done entirely within the entity
         * manager.
         */
        $sharedEventManager->attach(
            'Omeka\Entity\ApiKey',
            'entity.remove.post',
            function (Event $event) {
                $entity = $event->getTarget();
                $args = $event->getParam('LifecycleEventArgs');

                $eventEntity = new ActivityLogEvent;
                $eventEntity->setEvent($event->getName());
                $eventEntity->setResource($entity::class);
                $eventEntity->setResourceIdentifier($entity->getId());

                $activityLog = $this->getServiceLocator()->get('ActivityLog\ActivityLog');
                $activityLog->logEvent($eventEntity);
            }
        );

        /*
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

        /*
         * Add messages to user login event logs.
         */
        $sharedEventManager->attach(
            'user.login',
            'activity_log.event_messages',
            function (Event $event) {
                $view = $this->getServiceLocator()->get('ViewRenderer');
                $messages = $event->getParam('messages');
                $messages[] = $view->translate('Logged in');
                $event->setParam('messages', $messages);
            }
        );

        /*
         * Add messages to user logout event logs.
         */
        $sharedEventManager->attach(
            'user.logout',
            'activity_log.event_messages',
            function (Event $event) {
                $view = $this->getServiceLocator()->get('ViewRenderer');
                $messages = $event->getParam('messages');
                $messages[] = $view->translate('Logged out');
                $event->setParam('messages', $messages);
            }
        );

        /*
         * Add messages to API adapter event logs.
         */
        $eventIds = [
            'api.create.post',
            'api.update.post',
            'api.delete.post',
        ];
        foreach ($eventIds as $eventId) {
            $sharedEventManager->attach(
                $eventId,
                'activity_log.event_messages',
                function (Event $event) {
                    $view = $event->getTarget();
                    $loggedEvent = $event->getParam('loggedEvent');
                    $messages = $event->getParam('messages');
                    if ('api.create.post' === $loggedEvent->event()) {
                        $messages[] = sprintf($view->translate('Created a "%s" resource'), $loggedEvent->resource());
                    } elseif ('api.update.post' === $loggedEvent->event()) {
                        $messages[] = sprintf($view->translate('Updated a "%s" resource'), $loggedEvent->resource());
                    } elseif ('api.delete.post' === $loggedEvent->event()) {
                        $messages[] = sprintf($view->translate('Deleted a "%s" resource'), $loggedEvent->resource());
                    }
                    $messages[] = $view->translate('Source: API');
                    $messages[] = sprintf($view->translate('ID: %s'), $loggedEvent->resourceId());
                    $resource = $view->api()->searchOne($loggedEvent->resource(), ['id' => $loggedEvent->resourceId()])->getContent();
                    if ($resource) {
                        $messages[] = sprintf('<a href="%s">%s</a>', $view->escapeHtml($resource->url()), $view->translate('View resource'));
                    } else {
                        $messages[] = sprintf('[%s]', $view->translate('Resource not found'));
                    }
                    $event->setParam('messages', $messages);
                }
            );
        }

        /*
         * Add messages to Doctrine lifecycle event logs.
         */
        $eventIds = [
            'entity.persist.post',
            'entity.update.post',
            'entity.remove.post',
        ];
        foreach ($eventIds as $eventId) {
            $sharedEventManager->attach(
                $eventId,
                'activity_log.event_messages',
                function (Event $event) {
                    $view = $this->getServiceLocator()->get('ViewRenderer');
                    $loggedEvent = $event->getParam('loggedEvent');
                    $messages = $event->getParam('messages');
                    if ('entity.persist.post' === $loggedEvent->event()) {
                        $messages[] = sprintf($view->translate('Persisted a "%s" entity'), $loggedEvent->resource());
                    } elseif ('entity.update.post' === $loggedEvent->event()) {
                        $messages[] = sprintf($view->translate('Updated a "%s" entity'), $loggedEvent->resource());
                    } elseif ('entity.remove.post' === $loggedEvent->event()) {
                        $messages[] = sprintf($view->translate('Removed a "%s" entity'), $loggedEvent->resource());
                    }
                    $messages[] = $view->translate('Source: Doctrine');
                    $messages[] = sprintf($view->translate('ID: %s'), $loggedEvent->resourceId());
                    // Add message for media entities.
                    if ('Omeka\Entity\Media' === $loggedEvent->resource()) {
                        $resource = $view->api()->searchOne('media', ['id' => $loggedEvent->resourceId()])->getContent();
                        if ($resource) {
                            $messages[] = sprintf('<a href="%s">%s</a>', $view->escapeHtml($resource->url()), $view->translate('View resource'));
                        } else {
                            $messages[] = sprintf('[%s]', $view->translate('Resource not found'));
                        }
                    }
                    $event->setParam('messages', $messages);
                }
            );
        }

        /*
         * Add messages to API adapter batch event logs.
         */
        $eventIds = [
            'api.batch_create.post',
            'api.batch_update.post',
            'api.batch_delete.post',
        ];
        foreach ($eventIds as $eventId) {
            $sharedEventManager->attach(
                $eventId,
                'activity_log.event_messages',
                function (Event $event) {
                    $view = $this->getServiceLocator()->get('ViewRenderer');
                    $loggedEvent = $event->getParam('loggedEvent');
                    $messages = $event->getParam('messages');
                    if ('api.batch_create.post' === $loggedEvent->event()) {
                        $messages[] = sprintf($view->translate('Batch created "%s" resources'), $loggedEvent->resource());
                    } elseif ('api.batch_update.post' === $loggedEvent->event()) {
                        $messages[] = sprintf($view->translate('Batch updated "%s" resources'), $loggedEvent->resource());
                    } elseif ('api.batch_delete.post' === $loggedEvent->event()) {
                        $messages[] = sprintf($view->translate('Batch deleted "%s" resources'), $loggedEvent->resource());
                    }
                    $messages[] = $view->translate('Source: API');
                    $event->setParam('messages', $messages);
                }
            );
        }
    }
}
