<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace User\Model;
class User{
    protected $servicelocator = null;
    private function getServiceLocator() {
        return $this->servicelocator;
    }
    
    public function __construct($servicelocator){
        $this->servicelocator = $servicelocator;
    }

    public function getUserId($request) {
        $data = $request->getPost();
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $param = function($name) use ($dbadapter) {
            return $dbadapter->driver->formatParameterName($name);
        };
        $statement = $dbadapter->query(
                'SELECT * FROM users where usr_name=' . $param('usr_name') .
                ' and usr_password=' . $param('usr_password'));
        $result = $statement->execute(array('usr_name' => $data['usr_name'],
            'usr_password' => md5($data['usr_password'])));
        $row = $result->current();
        if (is_array($row)) {
            return $row;
        } else {
            return 0;
        }
    }

    public function getUserMenu($user_id) {
        $servicelocator = $this->getServiceLocator();
        $dbadapter = $servicelocator->get('Zend\Db\Adapter\Adapter');
        $param = function($name) use ($dbadapter) {
            return $dbadapter->driver->formatParameterName($name);
        };
        $statement = $dbadapter->query(
                'SELECT * FROM `module_master` WHERE id in(' .
                    'SELECT module_id FROM `role_collection` where role_id in(' .
                        'SELECT role_master_id FROM `privilege_master` where user_id=' . $param('usr_id') .
                    ')' .
                ')');
        $results = $statement->execute(array('usr_id' => $user_id));
        $returnArray = array();
        // iterate through the rows
        foreach ($results as $result) {
            $returnArray[] = $result;
        }
        return $returnArray;
    }
}