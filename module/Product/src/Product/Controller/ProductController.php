<?php

namespace Product\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Admin\Controller\IndexController;

class ProductController extends AbstractActionController {

    public function indexAction() {
        $checklogin = new IndexController();
        return $checklogin->checkLogin('Products',$this->getServiceLocator());
    }
}
