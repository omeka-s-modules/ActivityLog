# Activity Log

An [Omeka S](https://omeka.org/s/) module for monitoring user activity.

## Module configuration

The `events` database table may get very large over time. This may eventually have
an impact on the performance of your Omeka installation. Users may use the module
configuration form to reduce the size of the table by deleting all events before
a certain date.

## Events page

The "Events" page lists events, most recent first. It only lists events that modify
resources in some way (create, update, and delete). It does not include read-only
events (read and search). The table includes the following columns:

- **ID**: The ID of the event
- **Date**: The date/time of the event, using the installation's time zone
- **User**: The user who triggered the event, and the user's role
- **IP**: The IP address of the user, at the time of the event
- **Event**: The name of the event that was triggered by the user
- **Resource**: The name of the resource that was modified by the user
- **Messages**: Any messages that describe the event, in list form

Users may filter the events using the multiple available filters:

- **ID**: Filter events by event ID
- **User**: Filter events by user name (count in parenthesis)
- **User role**: Filter events by user role (count in parenthesis)
- **IP**: Filter events by IP address
- **Event**: Filter events by event name (count in parenthesis)
- **Resource**: Filter events by resource name (count in parenthesis)
- **Resource ID**: Filter events by resource ID
- **From**: Filter events by date from (on and after)
- **Before**: Filter events by date before

Set the filters and click "Apply filters." The resulting page will show the filtered
results. Click "Clear filters" to clear the filters and return to the default page.

## Events listened to

By default, the module will listen to the following events. Modules may add more
events, but they are not listed here.

- `user.login`
- `user.logout`
- `setting.insert`
    - for the "setting" resource
    - for the "site_setting" resource
    - for the "user_setting" resource
- `setting.update`
    - for the "setting" resource
    - for the "site_setting" resource
    - for the "user_setting" resource
- `setting.delete`
    - for the "setting" resource
    - for the "site_setting" resource
    - for the "user_setting" resource
- `api.create.post`for all API resources
- `api.update.post` for all API resources
- `api.delete.post` for all API resources
- `api.batch_create.post` for all API resources
- `api.batch_update.post` for all API resources
- `api.batch_delete.post` for all API resources
- `entity.persist.post`
    - for the "Omeka\Entity\Media" resource
    - for the "Omeka\Entity\ApiKey" resource
    - for the "Omeka\Entity\Module" resource
- `entity.update.post`
    - for the "Omeka\Entity\User" resource
    - for the "Omeka\Entity\Module" resource
- `entity.remove.post`
    - for the "Omeka\Entity\ApiKey" resource
    - for the "Omeka\Entity\Module" resource

<!--
- Manual: https://omeka.org/s/docs/user-manual/modules/activitylog/
- Developer docs: https://omeka.org/s/docs/developer/module_docs/ActivityLog/
-->

# Copyright

ActivityLog is Copyright © 2021-present Corporation for Digital Scholarship, Vienna,
Virginia, USA http://digitalscholar.org

The Corporation for Digital Scholarship distributes the Omeka source code under
the GNU General Public License, version 3 (GPLv3). The full text of this license
is given in the license file.

The Omeka name is a registered trademark of the Corporation for Digital Scholarship.

Third-party copyright in this distribution is noted where applicable.

All rights not expressly granted are reserved.
