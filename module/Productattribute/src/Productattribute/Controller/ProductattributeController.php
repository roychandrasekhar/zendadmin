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
        $all_attribute_type = $this->getAttributeType();
        $attribute_options =  $this->getOptionArray($request->getQuery('id'));
        $temp_type = $all_attribute_type[$productattributedetail['type']];
        return new ViewModel(array(
            'userdetail' => $adminloginuser->userdetail,
            'menus' => $menus,
            'controller' => 'Product Attribute',
            'islink' => true,
            'productattributedetail' => $productattributedetail,
            'attributetype'=> $all_attribute_type,
            'attributeoptiondata'=> (trim($temp_type)=='Select')?implode('--',$attribute_options):'',
        ));
    }
    
    public function getOptionArray($productattribute_id) {
        $attribute_select_option = $this->getTable('attribute_select_option');
        $results = $attribute_select_option
                ->select(array('productattribute_id' => $productattribute_id));
//                ->order(array('id','parent_id'));
        $returnArray = array();
        foreach ($results as $result) {
            $returnArray[] = $result['title'];
        }
        return $returnArray;
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
    
    public function uniqueattributeAction() {
        $request = $this->getRequest();
        $data = $request->getPost();
        $name = $data['name'];
        return array('attributelist' => $this->checkAttributeName($name));
    }
    
    private function checkAttributeName($name) {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $statement = $dbadapter->query(
                "select count(*) as total from productattribute where name='".$name."'");
        $results = $statement->execute();
        $total = 0;
        foreach ($results as $result) {
            $total = $result['total'];
        }
        return $total;
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
        if ($pagecounter)
            $pagecounter = $totalpage * $pagecounter;
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
        $optiontextval = '';
        if ($data['actiontype'] == 'delete') {
            $db->delete(array('id' => $data['id']));
        } elseif ($data['actiontype'] == 'update') {
            $postdata = array();
            $categoryid = '';
            foreach ($data as $key => $value) {
                if ($key == 'actiontype' || $key =='optiontext') {
                    continue;
                } else if ($key == 'optiontextval') {
                    $optiontextval = $value;
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
                if ($key == 'actiontype' || $key =='optiontext') {
                    continue;
                } else if ($key == 'optiontextval') {
                    $optiontextval = $value;
                    continue;
                } else {
                    $postdata[$key] = $value;
                }
            }
            $db->insert($postdata);
            $results = $db->select(array('name'=>$postdata['name']));
            foreach ($results as $result) {
                $data['id'] = $result['id'];
                break;
            }
        }
        if($optiontextval){
            $this->insertAttributeOptionValue($data['id'],$optiontextval);
        }
        return $this->redirect()->toRoute('productattribute/default', array('controller' => 'productattribute', 'action' => 'index'));
    }
    
    public function insertAttributeOptionValue($attribute_id, $optiontextval) {
        $alloption = explode(',', $optiontextval);
        $attribute_select_option = $this->getTable('attribute_select_option');
        
        $results = $attribute_select_option
                ->select(array('productattribute_id' => $attribute_id));
        $att_data = array();
        foreach ($results as $result) {
            $att_data[$result['title']] = $result['title'];
        }
        
        $data = array();
        foreach ($alloption as $value) {
            if(isset($att_data[$value]) && $att_data[$value]==$value){
                unset($att_data[$value]);
                continue;
            }
            $data['productattribute_id'] = $attribute_id;
            $data['title'] = $value;
            $attribute_select_option->insert($data);
        }
        
        // drop remaining option
        foreach($att_data as $key => $value){
            $attribute_select_option->delete(array('productattribute_id' => $attribute_id,
                'title'=>$value));
        }
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
