<?php

namespace Customer\Controller;

use Zend\Mvc\Controller\AbstractActionController;
//use Zend\View\Model\ViewModel;
use Admin\Controller\IndexController;

class CustomerController extends AbstractActionController {

    public function indexAction() {
        $checklogin = new IndexController();
        return $checklogin->checkLogin('Customer',$this->getServiceLocator());
    }
    public function editAction() {
        return array();
    }

}
