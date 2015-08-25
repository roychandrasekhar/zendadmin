<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Admin\Controller\IndexController;

class UserController extends AbstractActionController {

    public function indexAction() {
        $checklogin = new IndexController();
        return $checklogin->checkLogin('User', $this->getServiceLocator());
    }

}
