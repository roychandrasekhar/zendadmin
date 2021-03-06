<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Admin\Controller\IndexController;
use User\Model\User;

class UserController extends AbstractActionController {

    public function indexAction() {
        $checklogin = new IndexController();
        return $checklogin->checkLogin('Products',$this->getServiceLocator());
        
        $checklogin = new IndexController();
        $obj = $checklogin->checkLogin($this->getServiceLocator());
        if (is_object($obj)) {
            return $obj;
        } else {
            $user = new User($this->getServiceLocator());
            $adminloginuser = new Container('adminloginuser');
            $menus = $user->getUserMenu($adminloginuser->userid);
            return new ViewModel(array(
                'userdetail' => $adminloginuser->userdetail,
                'menus' => $menus,
                'controller' => 'User',
            ));
        }
    }

}
