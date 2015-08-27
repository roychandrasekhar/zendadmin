<?php

namespace Category\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Db\TableGateway\TableGateway;
use User\Model\User;

class CategoryController extends AbstractActionController {
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
                'controller' => 'Category',
            ));
        }
    }

    public function updateAction() {
        $request = $this->getRequest();
        
        
        $user = new User($this->getServiceLocator());
        $adminloginuser = new Container('adminloginuser');
        $menus = $user->getUserMenu($adminloginuser->userid);
        $view = new ViewModel(array(
            'userdetail' => $adminloginuser->userdetail,
            'menus' => $menus,
            'controller' => 'Category',
            'categorytree' => $this->getAllCategory(),
            'categorydetail' => $this->getCategoryDetail($request->getQuery('id')),
        ));
        return $view->setTemplate('/category/category/index.phtml');
    }

    private function getCategoryDetail($categoryid){
        $categoryTable = $this->getCategoryTable();
        $rowset = $categoryTable->select(array('id' => $categoryid));
        return $rowset->current();
    }
    private function getAllCategory() {
        $categoryTable = $this->getCategoryTable();
        $results = $categoryTable
                ->select();
//                ->order(array('id','parent_id'));
        $returnArray = array();
        // iterate through the rows
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
