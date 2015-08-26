<?php

namespace Customer\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Db\TableGateway\TableGateway;
use Admin\Controller\IndexController;
use User\Model\User;

class CustomerController extends AbstractActionController {

    private $customerTable;

    public function indexAction() {
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
                'controller' => 'Customer',
                'totalcustomer' => $this->getTotalCustomer(),
            ));
        }
    }

    public function editAction() {
        $checklogin = new IndexController();
        $obj = $checklogin->checkLogin($this->getServiceLocator());
        if ($obj) {
            return $obj;
        } else {
            $request = $this->getRequest();

            $user = new User($this->getServiceLocator());
            $adminloginuser = new Container('adminloginuser');
            $menus = $user->getUserMenu($adminloginuser->userid);
            return new ViewModel(array(
                'userdetail' => $adminloginuser->userdetail,
                'menus' => $menus,
                'controller' => 'Customeredit',
                'customerdetail' => $this->getCustomerCollection(0, 1, '', $request->getQuery('id')),
            ));
        }
    }

    public function listAction() {
        $request = $this->getRequest();
        $data = $request->getPost();
        $totalpage = $data['totalpage'];
        $pagecounter = $data['pageno'];
        $contactemail = $data['contactemail'];
        return array('customerlist' => $this->getCustomerCollection($pagecounter - 1, $totalpage, $contactemail));
    }

    private function getCustomerCollection($pagecounter = 0, $totalpage = 0, $contactemail, $customerid = '') {
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
        $statement = $dbadapter->query($sql . " limit $pagecounter,$totalpage ");
        $results = $statement->execute();
        $returnArray = array();
        // iterate through the rows
        foreach ($results as $result) {
            if ($customerid)
                return $result;
            $returnArray[] = $result;
        }
        return $returnArray;
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
        $postdata=array();
        foreach ($data as $key => $value) {
            $postdata[$key] = $value;
        }

        $db = $this->getCustomerTable();
        $db->update($postdata, 
                array('id' => $data['id'])
        );
        return $this->redirect()->toRoute('customer/default', array('controller' => 'customer', 'action' => 'index'));
    }

    public function getCustomerTable() {
        if (!$this->customerTable) {
            $this->customerTable = new TableGateway(
                    'customer', $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter')
            );
        }
        return $this->customerTable;
    }

}
