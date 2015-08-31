<?php

/**
 * Configuration file generated by ZFTool
 * The previous configuration file is stored in application.config.old
 *
 * @see https://github.com/zendframework/ZFTool
 */
return array(
    'static_salt' => 'aFGQ475SDsdfsaf2342',
    'modules' => array(
        'Application',
        'Admin',
        'Customer',
        'Product',
        'User',
        'Category',
        'Productattribute',
    ),
    'module_listener_options' => array(
        'module_paths' => array(
            './module',
            './vendor',
        ),
        'config_glob_paths' => array(
            'config/autoload/{{,*.}global,{,*.}local}.php',
        ),
    ),
);
