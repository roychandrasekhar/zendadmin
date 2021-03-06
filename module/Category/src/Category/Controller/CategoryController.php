<?php

namespace Category\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Db\TableGateway\TableGateway;
use User\Model\User;

class CategoryController extends AbstractActionController {

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
                'controller' => 'Category',
                'categorytree' => $this->getAllCategory(),
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
        $view = new ViewModel(array(
            'userdetail' => $adminloginuser->userdetail,
            'menus' => $menus,
            'controller' => 'Category',
            'categorytree' => $this->getAllCategory(),
            'islink' => true,
            'categorydetail' => 
                ($request->getQuery('id')!=0)?$this->getCategoryDetail($request->getQuery('id')):
                array('id'=>0,'parent_id'=>-1,'name'=>'Root','path'=>'')
                ,
        ));
        return $view->setTemplate('/category/category/index.phtml');
    }

    public function updateAction() {
        $request = $this->getRequest();
        $data = $request->getPost();

        $db = $this->getTable('category');
        if ($data['actiontype'] == 'delete') {
            $db->delete(array('id' => $data['id']));
        } elseif ($data['actiontype'] == 'update') {
            $postdata = array();
            foreach ($data as $key => $value) {
                if($key=='actiontype')continue;
                $postdata[$key] = $value;
            }

            $db->update($postdata, array('id' => $data['id'])
            );
        } elseif ($data['actiontype'] == 'addsub') {
            $user = new User($this->getServiceLocator());
            $adminloginuser = new Container('adminloginuser');
            $menus = $user->getUserMenu($adminloginuser->userid);
            $view = new ViewModel(array(
                'userdetail' => $adminloginuser->userdetail,
                'menus' => $menus,
                'controller' => 'Category',
                'categorytree' => $this->getAllCategory(),
                'parentcategoryid' => $data['id'],
                'parentcategoryname' => $data['name'],
            ));
            return $view->setTemplate('/category/category/index.phtml');
        }
        return $this->redirect()->toRoute('category/default', array('controller' => 'category', 'action' => 'index'));
    }

    public function addcategoryAction() {
        $request = $this->getRequest();
        $data = $request->getPost();

        $db = $this->getTable('category');
        $db->insert(
                array('parent_id' => $data['parent_id'],'name' => $data['category_name'])
        );
        return $this->redirect()->toRoute('category/default', array('controller' => 'category', 'action' => 'index'));
    }

    private function getCategoryDetail($categoryid) {
        $categoryTable = $this->getTable('category');
        $rowset = $categoryTable->select(array('id' => $categoryid));
        return $rowset->current();
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

    public function getTable($tablename) {
        if (!isset($this->tables[$tablename])) {
            $this->tables[$tablename] = new TableGateway(
                    $tablename, $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter')
            );
        }
        return $this->tables[$tablename];
    }
}
