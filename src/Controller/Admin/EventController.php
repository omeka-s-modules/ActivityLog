<?php
namespace ActivityLog\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class EventController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute('admin/activity-log/default', ['action' => 'browse'], true);
    }

    public function browseAction()
    {
        $perPage = 100;
        $this->setBrowseDefaults('created');
        $query = $this->params()->fromQuery();
        $query['per_page'] = $perPage;
        $response = $this->api()->search('activity_log_event', $query);
        $this->paginator(
            $response->getTotalResults(),
            $this->params()->fromQuery('page'),
            $perPage
        );
        $loggedEvents = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('loggedEvents', $loggedEvents);
        return $view;
    }
}
