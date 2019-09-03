<?php
namespace DV\Service ;

use Zend\Stdlib\ErrorHandler ;

class System
{
	const DOCTRINE_EM_KEY = 'doctrine.entitymanager.orm_default' ;
	
	const DOCTRINE_EM_ALIAS = 'Doctrine\ORM\EntityManager' ;
	
	const SUPERADMIN = 'superadmin';
	
	const CONTROLPANELADMIN = 'controlpaneladmin' ;
	
	const ROLE = 'role' ;
	
	const RESOURCE = 'resource' ;
	
	const ZF2_ACL_PATH = '/Application/src/%s/Acl/%s' ;
	
	
	static public function remove_session_file($session_id=null)
	{
		if(null == $session_id)	{
			$session_id = session_id() ;
		}
		 
		$session_file = realpath(APPLICATION_PATH . '/../data/session/sess_' . $session_id) ;
		
		### try to cache php core function error
		ErrorHandler::start();
		
		if(file_exists($session_file)) 	{
			#chmod($session_file , 0777) ;
			chown($session_file , 666) ;
			unlink($session_file) ;
		}
		else{ ### check the default php tmp folder
			$php_tmp_dir = ini_get('session.save_path') ;
			### check the directory
			if(is_dir($php_tmp_dir))    {
				### merge the filename with the directory
				$session_tmp_file = $php_tmp_dir .'/sess_' . $session_id ;
				### check for existence of the file
				if(file_exists($session_tmp_file))    {
					#chmod($session_tmp_file , '0777') ;
					unlink($session_tmp_file) ;
				}
			}
		}
		$error = ErrorHandler::stop();
		
		if($error)	{
			### run the windows deletion command
			if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')     {
				$lines = [] ; 
				$del = 1 ;
				exec("DEL /F/Q \".$session_file.\"" , $lines , $del) ;
				#throw new \Exception('unable to delete session file' , 0 , $error) ;
			}
		}
	}
}