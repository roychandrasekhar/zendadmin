<?php

namespace Admin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Authentication\Result;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;
use Admin\Model\Admin;
use Admin\Form\AdminForm;

class IndexController extends AbstractActionController {

    public function indexAction() {
        if ($user = $this->identity()) {
            return $this->redirect()->toRoute('admin/default', array('controller' => 'index', 'action' => 'login'));
        } else {
            return new ViewModel();
        }
    }

    public function loginAction() {
        $user = $this->identity();
        $form = new AdminForm();
        $form->get('submit')->setValue('Login');
        $messages = null;

        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($this->validateUser($request)) {
                $messages = "Successful login";
                return $this->redirect()->toRoute('admin/default', array('controller' => 'index', 'action' => 'index'));
            } else {
                $messages = "Error login";
            }
        }
        return new ViewModel(array('form' => $form, 'messages' => $messages, 'topLevel'=> 'menu'));
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

    private function validateUser($request) {
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
            return true;
        } else {
            return false;
        }
    }

}
