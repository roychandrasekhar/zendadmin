<?php

namespace Productattribute\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Db\TableGateway\TableGateway;
use User\Model\User;

class ProductattributeController extends AbstractActionController {

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
                'controller' => 'Product Attribute',
                'totalproductattribute' => $this->getTotalProductattribute(),
//                'messages' => realpath(dirname(__FILE__) . '/../../../../../public/images/products/'),
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
        $adminloginuser = new Container('adminloginuser');
        $menus = $user->getUserMenu($adminloginuser->userid);
        $productattributedetail = $this->getProductattributeCollection(0, 1, '', $request->getQuery('id'));
        return new ViewModel(array(
            'userdetail' => $adminloginuser->userdetail,
            'menus' => $menus,
            'controller' => 'Product Attribute',
            'islink' => true,
            'productattributedetail' => $productattributedetail,
            'attributetype'=> $this->getAttributeType()
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
        $adminloginuser = new Container('adminloginuser');
        $menus = $user->getUserMenu($adminloginuser->userid);
        return new ViewModel(array(
            'userdetail' => $adminloginuser->userdetail,
            'menus' => $menus,
            'controller' => 'Product Attribute',
            'islink' => true,
            'attributetype'=> $this->getAttributeType()
        ));
    }

    public function listAction() {
        $request = $this->getRequest();
        $data = $request->getPost();
        $totalpage = $data['totalpage'];
        $pagecounter = $data['pageno'];
        $attributename = $data['attributename'];
        return array(
            'productattributelist' => $this->getProductattributeCollection($pagecounter - 1, $totalpage, $attributename),
            'attributetype'=> $this->getAttributeType()
            );
    }

    private function getProductattributeCollection($pagecounter = 0, $totalpage = 0, $attributename, $productattributeid = '') {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $sql = 'select * from productattribute';
        if ($attributename != '') {
            $sql .= ' where name like "' . $attributename . '%"';
        } else if ($productattributeid != '') {
            $sql .= ' where id =' . $productattributeid;
        }
        $statement = $dbadapter->query($sql . " limit $pagecounter,$totalpage ");
        $results = $statement->execute();
        $returnArray = array();
        // iterate through the rows
        foreach ($results as $result) {
            if ($productattributeid)
                return $result;
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    private function getTotalProductattribute() {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $statement = $dbadapter->query(
                "select count(*) as total from productattribute");
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

        $db = $this->getTable('productattribute');
        if ($data['actiontype'] == 'delete') {
            $db->delete(array('id' => $data['id']));
        } elseif ($data['actiontype'] == 'update') {
            $postdata = array();
            $categoryid = '';
            foreach ($data as $key => $value) {
                if ($key == 'actiontype') {
                    continue;
                } else {
                    $postdata[$key] = $value;
                }
            }
            $db->update($postdata, array('id' => $data['id']));
        } elseif ($data['actiontype'] == 'add') {
            $postdata = array();
            $categoryid = '';
            foreach ($data as $key => $value) {
                if ($key == 'actiontype') {
                    continue;
                } else {
                    $postdata[$key] = $value;
                }
            }
            $db->insert($postdata);
        }
        return $this->redirect()->toRoute('productattribute/default', array('controller' => 'productattribute', 'action' => 'index'));
    }
    
    public function getAttributeType() {
        $categoryTable = $this->getTable('attributetype');
        $results = $categoryTable
                ->select();
        $returnArray = array();
        foreach ($results as $result) {
            $returnArray[$result['id']] = $result['name'];
        }
        return $returnArray;
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
