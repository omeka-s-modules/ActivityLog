<?php
namespace ActivityLog\Api\Representation;

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
            'o:created' => $this->getDateTime($this->created()),
            'o-module-faceted_browse:ip' => $this->ip(),
            'o-module-faceted_browse:event' => $this->event(),
            'o-module-faceted_browse:resource' => $this->resource(),
            'o-module-faceted_browse:resource_id' => $this->resourceId(),
            'o-module-faceted_browse:data' => $this->data(),
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

    public function created()
    {
        return $this->resource->getCreated();
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
        return $this->resource->getResourceId();
    }

    public function data()
    {
        return $this->resource->getData();
    }
}
