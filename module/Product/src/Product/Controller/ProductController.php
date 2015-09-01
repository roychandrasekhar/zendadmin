<?php

namespace Product\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Db\TableGateway\TableGateway;
use User\Model\User;

class ProductController extends AbstractActionController {

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
                'controller' => 'Products',
                'totalproduct' => $this->getTotalProduct(),
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
        $productdetail = $this->getProductCollection(0, 1, '', $request->getQuery('id'));
        return new ViewModel(array(
            'userdetail' => $adminloginuser->userdetail,
            'menus' => $menus,
            'controller' => 'Productedit',
            'customername' => $this->getCustomerName($productdetail['customer_id']),
            'controller' => 'Products',
            'islink' => true,
            'productdetail' => $productdetail,
            'categorytree' => $this->getAllCategory(),
            'categoryid' => $this->getProductCategoryId($request->getQuery('id')),
        ));
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

        $db = $this->getTable('product');
        if ($data['actiontype'] == 'delete') {
            $db->delete(array('id' => $data['id']));
        } elseif ($data['actiontype'] == 'update') {
            $postdata = array();
            $categoryid = '';
            foreach ($data as $key => $value) {
                if($key=='name' || $key=='description'||$key=='short_description'||$key=='status'
                        ||$key=='price'||$key=='imagepath'||$key=='inventory'||$key=='category'
                        ||$key=='customer_id'||$key=='approved'){
                    $postdata[$key] = $value;
                } else if ($key == 'category') {
                    $categoryid = $value;
                    continue;
                }
            }
            $db->update($postdata, array('id' => $data['id']));
            $this->setProductCategory($data['id'], $categoryid);

            // image part
            if ($_FILES['photo']['name']) {
                //if no errors...
                if (!$_FILES['photo']['error']) {
                    $valid_file=true;
                    //now is the time to modify the future file name and validate the file
                    $new_file_name = strtolower($_FILES['photo']['tmp_name']); //rename file
                    if ($_FILES['photo']['size'] > (1024000)) { //can't be larger than 1 MB
                        $valid_file = false;
                        $message = 'Oops!  Your file\'s size is to large.';
                    }

                    if ($valid_file) {
                        $imgname = '/'.$data['id'].'_'.$_FILES['photo']['name'];
                        move_uploaded_file($_FILES['photo']['tmp_name'], realpath(dirname(__FILE__) . '/../../../../../public/images/products/').$imgname);
                        $postdata = array();
                        $postdata['imagepath'] = 'images/products'.$imgname;
                        $db->update($postdata, array('id' => $data['id']));
                    }
                }
                //if there is an error...
                else {
                    //set that to be the returned message
                    $message = 'Ooops!  Your upload triggered the following error:  ' . $_FILES['photo']['error'];
                }
            }
            // image part
            
            $attributetablearray = $this->getAttributeTablename();
            // check for attribute
            foreach ($data as $key => $value) {
                $subatt = explode('||', $key);
                if($subatt[0]=='attribute'){
                    // get table name from id
                    $tablename = $attributetablearray[$subatt[2]];
                    
                    // delete old value for $data['id'] in that table
                    $attTable = $this->getTable($tablename);
                    $attTable->delete(array('product_id' => $data['id']));
                    
                    // insert new $value in that table
                    $data = array();
                    $data['product_id']=$data['id'];
                    $data['attributetype_id']=$subatt[1];
                    $data['value']=$value;
                    $attTable->insert();
                }
            }
            // check for attribute
        }
        return $this->redirect()->toRoute('product/default', array('controller' => 'product', 'action' => 'index'));
    }
    
    public function getAttributeTablename() {
        $categoryTable = $this->getTable('attributetype');
        $results = $categoryTable
                ->select();
        $returnArray = array();
        foreach ($results as $result) {
            $returnArray[$result['id']] = $result['tablename'];
        }
        return $returnArray;
    }

    public function getAllCategory() {
        $categoryTable = $this->getTable('category');
        $results = $categoryTable
                ->select();
//                ->order(array('id','parent_id'));
        $returnArray = array();
        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }

    public function setProductCategory($productid, $categoryid) {
        // delete all product category
        $db = $this->getTable('product_category');
        $db->delete(array('product_id' => $productid));

        // insert all new category for product
        $categoryarray = explode(',', $categoryid);
        foreach ($categoryarray as $eachcategoryid) {
            $db->insert(array('product_id' => $productid, 'category_id' => $eachcategoryid));
        }
    }

    public function getProductCategoryId($productid) {
        $db = $this->getTable('product_category');
        $results = $db->select(array('product_id' => $productid));
//                ->order(array('id','parent_id'));
        $returnArray = array();
        foreach ($results as $result) {
            $returnArray[] = $result['category_id'];
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

    public function getCustomerName($customerid) {
        $db = $this->getTable('customer');
        $results = $db->select(array('id' => $customerid));
        foreach ($results as $result) {
            return $result['name'];
        }
        return '';
    }

}
