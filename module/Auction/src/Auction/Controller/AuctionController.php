<?php

namespace Auction\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Db\TableGateway\TableGateway;
use Admin\Controller\IndexController;
use User\Model\User;

class AuctionController extends AbstractActionController {

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
                'controller' => 'Auction',
                'totalauction' => $this->getTotalAuction(),
            ));
        }
    }
    
    private function getTotalAuction() {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $statement = $dbadapter->query(
                "select count(*) as total from auction_master");
        $results = $statement->execute();
        $total = 0;
        foreach ($results as $result) {
            $total = $result['total'];
        }
        return $total;
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
            'controller' => 'Auction',
            'auctiondetail' => $this->getAuctionCollection(0, 1, '', $request->getQuery('id')),
        ));
    }

    public function listAction() {
        $request = $this->getRequest();
        $data = $request->getPost();
        $totalpage = $data['totalpage'];
        $pagecounter = $data['pageno'];
        $contactemail = $data['contactemail'];
        return array('auctionlist' => $this->getAuctionCollection($pagecounter - 1, $totalpage, $contactemail));
    }

    public function getAuctionCollection($pagecounter = 0, $totalpage = 0, $contactemail, $auctionid = '') {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
//        $param = function($name) use ($dbadapter) {
//            return $dbadapter->driver->formatParameterName($name);
//        };
        $sql = 'select * from auction_master';
        if ($contactemail != '') {
            $sql .= ' where email like "' . $contactemail . '%"';
        } else if ($auctionid != '') {
            $sql .= ' where id =' . $auctionid;
        }
        if ($pagecounter)
            $pagecounter = $totalpage * $pagecounter;
        $statement = $dbadapter->query($sql . " limit $pagecounter,$totalpage ");
        $results = $statement->execute();
        $returnArray = array();
        // iterate through the rows
        
        
        foreach ($results as $result) {
            if($result['product_id']){
                $result['product_detail'] = $this->getProductDetail($result['product_id']);
            }
            $result['biddeatil'] = $this->getBidDetail($result['id']);
            if ($auctionid != '') {
                return $result;
            } else {
                $returnArray[] = $result;
            }
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
    public function getProductDetail($productid) {
        $db = $this->getTable('product');
        $results = $db->select(array('id' => $productid));
        foreach($results as $result){
            $customer_detail = $this->getCustomerDetail($result['customer_id']);
            $result['customer_name'] = $customer_detail['name'];
            return $result;
        }
    }
    public function getBidDetail($auctionid) {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $statement = $dbadapter->query(
                "SELECT count(*) as count,max(`bid_amount`) as max FROM `bid` WHERE `auction_id`=".$auctionid);
        $results = $statement->execute();
        $total = 0;
        foreach ($results as $result) {
            return array('count'=>$result['count'],'max'=>$result['max']);
        }
    }
    public function getCustomerDetail($customerid) {
        $db = $this->getTable('customer');
        $results = $db->select(array('id' => $customerid));
        foreach($results as $result){
            return $result;
        }
    }

}
