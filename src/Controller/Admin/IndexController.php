<?php
namespace ActivityLog\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute('admin/activity-log/default', ['controller' => 'event', 'action' => 'browse']);
    }
}
