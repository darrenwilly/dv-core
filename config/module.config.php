<?php

if(! defined('TROJAN_ACL'))    {
    ##
    define('TROJAN_ACL' , 'Trojan.ACL') ;
}
if(! defined('TROJAN_ACL_CONTROLLER'))    {
    ##
    define('TROJAN_ACL_CONTROLLER' , 'Trojan.ACL.Controller') ;
}
if(! defined('TROJAN_ACL_CONTROLLER_ACTION'))    {
    ##
    define('TROJAN_ACL_CONTROLLER_ACTION' , 'Trojan.ACL.Action') ;
}

$config = [
	'controllers' => [
		'invokables' => [
			\DV\Mvc\Controller\ActionController::class => \DV\Mvc\Controller\ActionController::class,
			\DV\Mvc\Controller\AuthenticatedActionController::class => DV\Mvc\Controller\AuthenticatedActionController::class ,
		],
	],
		
	'controller_plugins' => [
		'invokables' => [
			'Acl' => 'DV\Mvc\Controller\Plugin\Acl' ,
		]
	],

	'dv_cache' => [		
		'routes' => [
				'login' => [],
				
		],
	],

];

return $config ;
