<?php
namespace ActivityLog\Controller\Admin;

use ActivityLog\Form\EventFilterForm;
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
        $eventFilterForm = $this->getForm(EventFilterForm::class);
        $eventFilterForm->setData($this->params()->fromQuery());

        $this->setBrowseDefaults('created');
        $query = $this->params()->fromQuery();
        $query['per_page'] = 100;
        $response = $this->api()->search('activity_log_event', $query);
        $this->paginator(
            $response->getTotalResults(),
            $this->params()->fromQuery('page'),
            $query['per_page']
        );
        $loggedEvents = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('loggedEvents', $loggedEvents);
        $view->setVariable('eventFilterForm', $eventFilterForm);
        return $view;
    }
}
