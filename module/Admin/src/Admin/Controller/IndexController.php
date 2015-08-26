<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Authentication\Result;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;
use Zend\Session\Container;
use Admin\Model\Admin;
use Admin\Form\AdminForm;
use User\Model\User;

class IndexController extends AbstractActionController {

    public function indexAction() {
        $obj = $this->checkLogin($this->getServiceLocator());
        if (is_object($obj)) {
            return $obj;
        } else {
            $user = new User($this->getServiceLocator());
            $adminloginuser = new Container('adminloginuser');
            $menus = $user->getUserMenu($adminloginuser->userid);
            return new ViewModel(array(
                'userdetail' => $adminloginuser->userdetail,
                'menus' => $menus,
                'controller' => 'Index',
            ));
        }
    }

    public function logindetail($serviceLocator) {
        $user = new User($serviceLocator);
        $adminloginuser = new Container('adminloginuser');
        return $user->getUserMenu($adminloginuser->userid);
    }

    public function checkLogin($serviceLocator) {
        $container = new Container('adminloginuser');
        if ($container->userid == '') { // this section is not working. Need some more work here
            return $this->redirect()->toRoute('admin/default', array(
                        'controller' => 'index',
                        'action' => 'login'
            ));
        } else {
            return 0;
        }
    }

    public function loginAction() {
//        $user = $this->identity();
        $user = new User($this->getServiceLocator());
        $form = new AdminForm();
        $form->get('submit')->setValue('Login');
        $messages = null;

        $request = $this->getRequest();
        if ($request->isPost()) {
            $userdetail = $user->getUserId($request);
            if ($userdetail) {
                $messages = "Successful login";
                $container = new Container('adminloginuser');
                $container->userdetail = $userdetail;
                $container->userid = $userdetail['id'];
                return $this->redirect()->toRoute('admin/default', array(
                            'controller' => 'index',
                            'action' => 'index',
                ));
            } else {
                $messages = "Error login";
                return new ViewModel(array(
                    'form' => $form,
                    'messages' => $messages,
                ));
            }
        } else {
            return new ViewModel(array(
                'form' => $form,
            ));
        }
    }

    public function logoutAction() {
        $auth = new AuthenticationService();
        // or prepare in the globa.config.php and get it from there
        // $auth = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');

        if ($auth->hasIdentity()) {
            $identity = $auth->getIdentity();
        }
        $container = new Container('adminloginuser');
        unset($container->userdetail);
        unset($container->userid);

        $auth->clearIdentity();
//		$auth->getStorage()->session->getManager()->forgetMe(); // no way to get the sessionmanager from storage
        $sessionManager = new \Zend\Session\SessionManager();
        $sessionManager->forgetMe();

        return $this->redirect()->toRoute('admin/default', array('controller' => 'index', 'action' => 'login'));
    }

}
