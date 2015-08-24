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

class IndexController extends AbstractActionController {

    public function indexAction() {
        if ($user = $this->identity()) { // this section is not working. Need some more work here
            return $this->redirect()->toRoute('admin/default', array(
                        'controller' => 'index',
                        'action' => 'login'
            ));
        } else {
            $adminloginuser = new Container('adminloginuser');
            // load menu for user $adminloginuser->userid
            $menus = $this->getUserMenu($adminloginuser->userid);
//            print_r(json_encode($menus));
            return new ViewModel(array(
                'user_id' => $adminloginuser->userid,
                'menus' => $menus,
            ));
        }
    }

    public function loginAction() {
        $user = $this->identity();
        $form = new AdminForm();
        $form->get('submit')->setValue('Login');
        $messages = null;

        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($user_id = $this->getUserId($request)) {
                $messages = "Successful login";
                $container = new Container('adminloginuser');
                $container->userid = $user_id;
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

        $auth->clearIdentity();
//		$auth->getStorage()->session->getManager()->forgetMe(); // no way to get the sessionmanager from storage
        $sessionManager = new \Zend\Session\SessionManager();
        $sessionManager->forgetMe();

        return $this->redirect()->toRoute('admin/default', array('controller' => 'index', 'action' => 'login'));
    }

    private function getUserId($request) {
        $data = $request->getPost();
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $param = function($name) use ($dbadapter) {
            return $dbadapter->driver->formatParameterName($name);
        };
        $statement = $dbadapter->query(
                'SELECT * FROM users where usr_name=' . $param('usr_name') .
                ' and usr_password=' . $param('usr_password'));
        $result = $statement->execute(array('usr_name' => $data['usr_name'],
            'usr_password' => md5($data['usr_password'])));
        $row = $result->current();
        if (is_array($row)) {
            return $row['id'];
        } else {
            return 0;
        }
    }

    private function getUserMenu($user_id) {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $param = function($name) use ($dbadapter) {
            return $dbadapter->driver->formatParameterName($name);
        };
        $statement = $dbadapter->query(
                'SELECT * FROM `module_master` WHERE id in(' .
                    'SELECT module_id FROM `role_collection` where role_id in(' .
                        'SELECT role_master_id FROM `privilege_master` where user_id=' . $param('usr_id') .
                    ')' .
                ')');
        $results = $statement->execute(array('usr_id' => $user_id));
        $returnArray = array();
        // iterate through the rows
        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

}
