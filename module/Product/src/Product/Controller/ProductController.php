<?php

namespace Product\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Db\TableGateway\TableGateway;
use Admin\Controller\IndexController;
use User\Model\User;

class ProductController extends AbstractActionController {

    private $productTable;
    private $categoryTable;
    
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
                'controller' => 'Product',
                'totalproduct' => $this->getTotalProduct(),
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
                'controller' => 'Productedit',
                'productdetail' => $this->getProductCollection(0, 1, '', $request->getQuery('id')),
                'categorytree' => $this->getAllCategory(),
            ));
        }
    }

    public function listAction() {
        $request = $this->getRequest();
        $data = $request->getPost();
        $totalpage = $data['totalpage'];
        $pagecounter = $data['pageno'];
        $contactemail = $data['contactemail'];
        return array('productlist' => $this->getProductCollection($pagecounter - 1, $totalpage, $contactemail));
    }

    private function getProductCollection($pagecounter = 0, $totalpage = 0, $contactemail, $productid = '') {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
//        $param = function($name) use ($dbadapter) {
//            return $dbadapter->driver->formatParameterName($name);
//        };
        $sql = 'select * from product';
        if ($contactemail != '') {
            $sql .= ' where email like "' . $contactemail . '%"';
        } else if ($productid != '') {
            $sql .= ' where id =' . $productid;
        }
        $statement = $dbadapter->query($sql . " limit $pagecounter,$totalpage ");
        $results = $statement->execute();
        $returnArray = array();
        // iterate through the rows
        foreach ($results as $result) {
            if ($productid)
                return $result;
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    private function getTotalProduct() {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $statement = $dbadapter->query(
                "select count(*) as total from product");
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
        
        $db = $this->getProductTable();
        if($data['actiontype']=='delete'){
            $db->delete(array('id' => $data['id']));
        }elseif($data['actiontype']=='update'){
            $postdata=array();
            foreach ($data as $key => $value) {
                if($key=='actiontype')continue;
                $postdata[$key] = $value;
            }

            $db->update($postdata, 
                    array('id' => $data['id'])
            );
            
        }
        return $this->redirect()->toRoute('product/default', array('controller' => 'product', 'action' => 'index'));
    }

    public function getProductTable() {
        if (!$this->productTable) {
            $this->productTable = new TableGateway(
                    'product', $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter')
            );
        }
        return $this->productTable;
    }
    
    public function getAllCategory() {
        $categoryTable = $this->getCategoryTable();
        $results = $categoryTable
                ->select();
//                ->order(array('id','parent_id'));
        $returnArray = array();
        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }
    public function getCategoryTable() {
        if (!$this->categoryTable) {
            $this->categoryTable = new TableGateway(
                    'category', $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter')
            );
        }
        return $this->categoryTable;
    }

}
