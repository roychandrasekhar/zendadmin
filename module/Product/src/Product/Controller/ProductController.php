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
//                'messages' => $this->getActiveAttributes(),
            ));
        }
    }

    public function getActiveAttributes($product_id) {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $sql = "SELECT pro_att.id, pro_att.name,pro_att.description,pro_att.type,att_type.name as attribute_type,att_type.tablename
                ,att_st.value as att_string_val
                ,att_in.value as att_integer_val
                ,att_de.value as att_decimal_val
                ,att_bo.value as att_boolean_val
                ,att_sel.value as att_select_val
                ,att_date.value as att_date_val
                FROM productattribute as pro_att 
                left join attributetype as att_type on pro_att.type=att_type.id 
                left join attribute_string as att_st on (pro_att.id=att_st.attributetype_id and att_st.product_id=$product_id)
                left join attribute_integer as att_in on (pro_att.id=att_in.attributetype_id and att_in.product_id=$product_id)
                left join attribute_decimal as att_de on (pro_att.id=att_de.attributetype_id and att_de.product_id=$product_id)
                left join attribute_boolean as att_bo on (pro_att.id=att_bo.attributetype_id and att_bo.product_id=$product_id)
                left join attribute_select as att_sel on (pro_att.id=att_sel.attributetype_id and att_sel.product_id=$product_id)
                left join attribute_date as att_date on (pro_att.id=att_date.attributetype_id and att_date.product_id=$product_id)
                where pro_att.active=1";
        $statement = $dbadapter->query($sql);
        $results = $statement->execute();
        $returnArray = array();
        // iterate through the rows
        foreach ($results as $result) {
            if (strtolower($result['attribute_type']) == 'select') {
                // get all that option
                $result['options'] = $this->getAttributeSelectOption($result['id']);
            }
            $returnArray[] = $result;
        }
        return $returnArray;
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
            'activeattributes' => $this->getActiveAttributes($request->getQuery('id')),
        ));
    }

    public function listAction() {
        $request = $this->getRequest();
        $data = $request->getPost();
        $totalpage = $data['totalpage'];
        $pagecounter = $data['pageno'];
        $productname = $data['productname'];
        return array(
            'productlist' => $this->getProductCollection($pagecounter - 1, $totalpage, $productname),
            'admin_attribute_list' => $this->getAdminAttributeList('name'),
        );
    }

    public function getAdminAttributeList($field = null) {
        $productattributeTable = $this->getTable('productattribute');
        $results = $productattributeTable
                ->select(array('active' => 1, 'admin_list' => 1));
//                ->order(array('id','parent_id'));
        $returnArray = array();
        foreach ($results as $result) {
            if ($field) {
                $returnArray[] = $result[$field];
            } else {
                $returnArray[] = $result;
            }
        }
        return $returnArray;
    }

    private function getProductCollection($pagecounter = 0, $totalpage = 0, $productname, $productid = '') {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $sql = 'select * from product';
        if ($productname != '') {
            $sql .= ' where name like "' . $productname . '%"';
        } else if ($productid != '') {
            $sql .= ' where id =' . $productid;
        }
        if ($pagecounter)
            $pagecounter = $totalpage * $pagecounter;
        $statement = $dbadapter->query($sql . " limit $pagecounter,$totalpage ");
        $results = $statement->execute();
        $returnArray = array();
        // iterate through the rows
        foreach ($results as $result) {
            $result['imagepath'] = $this->getImagePathArray($result['id']);
            $result['attributedata'] = $this->getActiveAttributes($result['id']);
            
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

        $categoryid = '';
        $default_image = '';
        $db = $this->getTable('product');
        if ($data['actiontype'] == 'delete') {
            $db->delete(array('id' => $data['id']));
        } elseif ($data['actiontype'] == 'save' || $data['actiontype'] == 'save_continue') {
            $postdata = array();
            foreach ($data as $key => $value) {
                if ($key == 'name' || $key == 'description' || $key == 'short_description' || $key == 'status' || $key == 'price' || $key == 'imagepath' || $key == 'inventory' || $key == 'customer_id' || $key == 'approved') {
                    $postdata[$key] = $value;
                } else if ($key == 'category') {
                    $categoryid = $value;
                    continue;
                } else if ($key == 'default_image') {
                    $default_image = $value;
                    continue;
                }
            }
            $db->update($postdata, array('id' => $data['id']));
            $this->setProductCategory($data['id'], $categoryid);

            // image part
            if ($_FILES['photo']['name']) {
                //if no errors...
                if (!$_FILES['photo']['error']) {
                    $valid_file = true;
                    //now is the time to modify the future file name and validate the file
                    $new_file_name = strtolower($_FILES['photo']['tmp_name']); //rename file
                    if ($_FILES['photo']['size'] > (1024000)) { //can't be larger than 1 MB
                        $valid_file = false;
                        $message = 'Oops!  Your file\'s size is to large.';
                    }

                    if ($valid_file) {
                        $imgname = '/' . $data['id'] . '_' . $_FILES['photo']['name'];
                        move_uploaded_file($_FILES['photo']['tmp_name'], realpath(dirname(__FILE__) . '/../../../../../public/images/products/') . $imgname);
                        $db = $this->getTable('product_image');
                        $postdata = array();
                        $postdata['product_id'] = $data['id'];
                        $postdata['imagepath'] = 'images/products' . $imgname;
                        $db->delete(array('product_id' => $data['id'],
                            'imagepath' => 'images/products' . $imgname));
                        $db->insert($postdata);
                    }
                }
                //if there is an error...
                else {
                    //set that to be the returned message
                    $message = 'Ooops!  Your upload triggered the following error:  ' . $_FILES['photo']['error'];
                }
            }
            // image part
            // default image if found update
            if ($default_image) {
                $image_db = $this->getTable('product_image');
                $image_db->update(array('is_default' => 0), array('is_default' => 1, 'product_id' => $data['id']));
                $image_db->update(array('is_default' => 1), array('id' => $default_image));
            }
            // default image if found update
            // check for attribute
            foreach ($data as $key => $value) {
                $subatt = explode('||', $key);
                if ($subatt[0] == 'attribute') {
                    // get table name from id
                    $tablename = $subatt[2];

                    // delete old value for $data['id'] in that table
                    $attTable = $this->getTable($tablename);
                    $attTable->delete(array('product_id' => $data['id'],
                        'attributetype_id' => $subatt[1]));

                    // insert new $value in that table
                    $newdata = array();
                    $newdata['product_id'] = $data['id'];
                    $newdata['attributetype_id'] = $subatt[1];
                    if($tablename=='attribute_date'){
                        $newdata['value'] = strtotime($value);
                    }else{
                        $newdata['value'] = $value;
                    }
                    $attTable->insert($newdata);
                }
            }
            // check for attribute
        } elseif ($data['actiontype'] == 'remove_image' || $data['remove_image_id'] != '') {
            // remove file $data['remove_image_path']
            unlink(realpath(dirname(__FILE__)) . '/../../../../../public/' . $data['remove_image_path']);
            $db = $this->getTable('product_image');
            $db->delete(array('id' => $data['remove_image_id']));
        }

        if ($data['actiontype'] == 'save_continue' || $data['actiontype'] == 'remove_image') {
            $user = new User($this->getServiceLocator());
            $adminloginuser = new Container('adminloginuser');
            $menus = $user->getUserMenu($adminloginuser->userid);
            $productdetail = $this->getProductCollection(0, 1, '', $data['id']);
            $view = new ViewModel(array(
                'userdetail' => $adminloginuser->userdetail,
                'menus' => $menus,
//                'controller' => 'Productedit',
                'customername' => $this->getCustomerName($productdetail['customer_id']),
                'controller' => 'Products',
                'islink' => true,
                'productdetail' => $productdetail,
                'categorytree' => $this->getAllCategory(),
                'categoryid' => $this->getProductCategoryId($data['id']),
                'activeattributes' => $this->getActiveAttributes($data['id']),
//                'message' => $categoryid,
            ));
            return $view->setTemplate('/product/product/edit.phtml');
        }

        return $this->redirect()->toRoute('product/default', array('controller' => 'product', 'action' => 'index'));
    }

    public function getAttributeTablename() {
        $attTable = $this->getTable('attributetype');
        $results = $attTable->select();
        $returnArray = array();
        foreach ($results as $result) {
            $returnArray[$result['id']] = $result['tablename'];
        }
        return $returnArray;
    }

    public function getImagePathArray($product_id) {
        $attTable = $this->getTable('product_image');
        $results = $attTable->select(array('product_id' => $product_id));
        $returnArray = array();
        foreach ($results as $result) {
            $returnArray[$result['id']] = array($result['imagepath'], $result['is_default']);
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

    public function getAttributeSelectOption($pro_att_id) {
        $attribute_select_option = $this->getTable('attribute_select_option');
        $results = $attribute_select_option
                ->select(array('productattribute_id' => $pro_att_id));
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
