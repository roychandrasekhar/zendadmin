<?php

namespace Customer\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Db\TableGateway\TableGateway;
use Admin\Controller\IndexController;
use User\Model\User;

class CustomerController extends AbstractActionController {

    private $tables;

    public function indexAction() {
        $container = new Container('adminloginuser');
        if ($container->userid == '') { // this section is not working. Need some more work here
            return $this->redirect()->toRoute('admin/default', array(
                        'controller' => 'index',
                        'action' => 'login'
            ));
        } else {
            $user = new User($this->getServiceLocator());
            $adminloginuser = new Container('adminloginuser');
            $menus = $user->getUserMenu($adminloginuser->userid);
            return new ViewModel(array(
                'userdetail' => $adminloginuser->userdetail,
                'menus' => $menus,
                'controller' => 'Customer',
                'totalcustomer' => $this->getTotalCustomer(),
            ));
        }
    }

    public function editAction() {
        $container = new Container('adminloginuser');
        if ($container->userid == '') { // this section is not working. Need some more work here
            return $this->redirect()->toRoute('admin/default', array(
                        'controller' => 'index',
                        'action' => 'login'
            ));
        }
        $request = $this->getRequest();

        $user = new User($this->getServiceLocator());
//        $adminloginuser = new Container('adminloginuser');
        $menus = $user->getUserMenu($container->userid);
        return new ViewModel(array(
            'userdetail' => $container->userdetail,
            'islink' => true,
            'menus' => $menus,
            'controller' => 'Customeredit',
            'customerdetail' => $this->getCustomerCollection(0, 1, '', $request->getQuery('id')),
        ));
    }

    public function addAction() {
        $container = new Container('adminloginuser');
        if ($container->userid == '') { // this section is not working. Need some more work here
            return $this->redirect()->toRoute('admin/default', array(
                        'controller' => 'index',
                        'action' => 'login'
            ));
        }

        $user = new User($this->getServiceLocator());
//        $adminloginuser = new Container('adminloginuser');
        $menus = $user->getUserMenu($container->userid);
        return new ViewModel(array(
            'userdetail' => $container->userdetail,
            'islink' => true,
            'menus' => $menus,
            'controller' => 'Customeredit',
        ));
    }

    public function listAction() {
        $request = $this->getRequest();
        $data = $request->getPost();
        $totalpage = $data['totalpage'];
        $pagecounter = $data['pageno'];
        $contactemail = $data['contactemail'];
        return array('customerlist' => $this->getCustomerCollection($pagecounter - 1, $totalpage, $contactemail));
    }
    
    public function uniqueemailAction() {
        $request = $this->getRequest();
        $data = $request->getPost();
        $email = $data['email'];
        return array('customerlist' => $this->checkCustomerEmail($email));
    }

    public function getCustomerCollection($pagecounter = 0, $totalpage = 0, $contactemail, $customerid = '') {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
//        $param = function($name) use ($dbadapter) {
//            return $dbadapter->driver->formatParameterName($name);
//        };
        $sql = 'select * from customer';
        if ($contactemail != '') {
            $sql .= ' where email like "' . $contactemail . '%"';
        } else if ($customerid != '') {
            $sql .= ' where id =' . $customerid;
        }
        if($pagecounter) $pagecounter = $totalpage*$pagecounter;
        $statement = $dbadapter->query($sql . " limit $pagecounter,$totalpage ");
        $results = $statement->execute();
        $returnArray = array();
        // iterate through the rows
        foreach ($results as $result) {
            if ($customerid != '') {
                return $result;
            } else {
                $returnArray[] = $result;
            }
        }
        return $returnArray;
    }

    private function checkCustomerEmail($email) {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $statement = $dbadapter->query(
                "select count(*) as total from customer where email='".$email."'");
        $results = $statement->execute();
        $total = 0;
        foreach ($results as $result) {
            $total = $result['total'];
        }
        return $total;
//        return "select count(*) as total from customer where email='".$email."'";
    }
    private function getTotalCustomer() {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $statement = $dbadapter->query(
                "select count(*) as total from customer");
        $results = $statement->execute();
        $total = 0;
        foreach ($results as $result) {
            $total = $result['total'];
        }
        return $total;
    }

    public function updateAction() {
        $request = $this->getRequest();
        $data = $request->getPost();

        $db = $this->getTable('customer');
        if ($data['actiontype'] == 'delete') {
            $db->delete(array('id' => $data['id']));
        } elseif ($data['actiontype'] == 'add') {
            $postdata = array();
            foreach ($data as $key => $value) {
                if ($key == 'actiontype') {
                    continue;
                }
                $postdata[$key] = $value;
            }
            $db->insert($postdata);
        } elseif ($data['actiontype'] == 'update') {
            $postdata = array();
            foreach ($data as $key => $value) {
                if ($key == 'password') {
                    if ($value == '') {
                        continue;
                    } else {
                        $value = md5($value);
                    }
                } else if ($key == 'actiontype') {
                    continue;
                }
                $postdata[$key] = $value;
            }

            $db->update($postdata, array('id' => $data['id'])
            );
        }
        return $this->redirect()->toRoute('customer/default', array('controller' => 'customer', 'action' => 'index'));
    }

    public function getTable($tablename) {
        if (!isset($this->tables[$tablename])) {
            $this->tables[$tablename] = new TableGateway(
                    $tablename, $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter')
            );
        }
        return $this->tables[$tablename];
    }

}
