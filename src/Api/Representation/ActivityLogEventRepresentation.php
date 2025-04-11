<?php
namespace ActivityLog\Api\Representation;

use DateTime;
use DateTimeZone;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractEntityRepresentation;

class ActivityLogEventRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o-module-activity_log:Event';
    }

    public function getJsonLd()
    {
        $user = $this->user();
        return [
            'o:user' => $user ? $user->getReference() : null,
            'o:timestamp' => $this->getDateTime($this->timestamp()),
            'o-module-activity_log:ip' => $this->ip(),
            'o-module-activity_log:event' => $this->event(),
            'o-module-activity_log:resource' => $this->resource(),
            'o-module-activity_log:resource_id' => $this->resourceId(),
            'o-module-activity_log:data' => $this->data(),
        ];
    }

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/activity-log/id',
            [
                'controller' => 'event',
                'action' => $action,
                'event-id' => $this->id(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    public function user()
    {
        return $this->getAdapter('users')->getRepresentation($this->resource->getUser());
    }

    public function timestamp()
    {
        return $this->resource->getTimestamp();
    }

    public function dateTime()
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $dateTime = new DateTime('@' . $this->timestamp());
        $dateTime->setTimezone(new DateTimeZone($settings->get('time_zone', 'UTC')));
        return $dateTime;
    }

    public function ip()
    {
        return $this->resource->getIp();
    }

    public function event()
    {
        return $this->resource->getEvent();
    }

    public function resource()
    {
        return $this->resource->getResource();
    }

    public function resourceId()
    {
        return $this->resource->getResourceIdentifier();
    }

    public function data()
    {
        return $this->resource->getData();
    }

    public function messages(PhpRenderer $view)
    {
        $eventParams = $view->trigger(
            'activity_log.event_messages',
            ['loggedEvent' => $this, 'messages' => []],
            true,
            [$this->event()]
        );
        $messages = $eventParams['messages'];
        $messages[] = $view->hyperlink($view->translate('View event data'), '#', [
            'data-sidebar-selector' => '#sidebar',
            'data-sidebar-content-url' => $view->url('admin/activity-log/id', ['action' => 'show-data', 'id' => $this->id()], true),
            'class' => 'sidebar-content',
        ]);
        return $messages;
    }
}
