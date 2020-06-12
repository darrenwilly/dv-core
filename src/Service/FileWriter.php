<?php
namespace DV\Service ;

class FileWriter
{
	
	/**
	 * default modules to use as folder class name
	 * @var string
	 */
	const DEFAULT_MODULE = 'veiw' ;


	/**
	 * create a ACL Role base class file as string/stream on the file system
	 *
	 * @param $acl_writer
	 * @param string $acl_type
	 * @param string $moduleFolder the module to create the file in
	 * @return bool|void
	 * @internal param string $acl_role the role to create its file
	 */
	public static function aclFileWriter($acl_writer , $acl_type='role' , $moduleFolder=self::DEFAULT_MODULE)
	{
		return self::_createClassFile($acl_writer , $acl_type , $moduleFolder) ;
	}


	/**
	 * create a ACL Role base class file as string/stream on the file system
	 *
	 * @param $acl
	 * @param string $acl_type
	 * @param string $moduleFolder the module to create the file in
	 * @return bool|void
	 * @internal param string $acl_role the role to create its file
	 */
	protected static function _createClassFile($acl , $acl_type='role' , $moduleFolder=self::DEFAULT_MODULE)
	{		
		### path to the acl role directory
		$acl_dir = realpath(APPLICATION_PATH . sprintf(System::ZF2_ACL_PATH, ucfirst($moduleFolder) , ucfirst($acl_type))) ;
		
		### make the acl role file string
		$filename = $acl_dir .'/'. ucfirst($acl) . '.php' ;
		
		### check for the existence of the file.
		if(! file_exists($filename))	{
			### open or create the file as needed in a variable handle
			$open = @fopen($filename, 'a+') ;
				### check for opened file
				if($open)	{
					### switch statement to select the class to write
					SWITCH(strtolower($acl_type))	{
						case 'role' :
								### start writing a class style of file
								$write = fwrite($open , self::_writeAclRoleClassString($acl , $moduleFolder)) ;
							break ;
							
						case 'resource' :
								### start writing a class style of file
								$write = fwrite($open , self::_writeAclResourcesClassString($acl , $moduleFolder)) ;
							break; 
					}
					
					### check for successful file write
					if($write)	{
						### gladly close up the file and free memory
						fclose($open) ;
					}
					
					return ;
				}
		}
		
		return true ;
	}
	
	
	/**
	 * create a ACL Role class string that implement the Laminas_Role_Interface
	 * 
	 * @param string $acl_role the role to create its file
	 * @param string $moduleFolder the module to create the file in
	 */
	protected static function _writeAclRoleClassString($acl_role , $moduleFolder=self::DEFAULT_MODULE)
	{
		$string = '<?php
namespace '.ucfirst($moduleFolder).'\Core\Acl\Role ;	 
					
class '.ucfirst($acl_role).'
{
					
	const ROLE = \''.strtolower($acl_role).'\';						
						
	public function getRoleId() 
	{ 
		return self::ROLE ; 
	}
						
}';
		
		return $string ;
	}


	/**
	 * create a ACL Role class string that implement the Laminas_Role_Interface
	 *
	 * @param $acl_res
	 * @param string $moduleFolder the module to create the file in
	 * @return string
	 * @internal param string $acl_role the role to create its file
	 */
	protected static function _writeAclResourcesClassString($acl_res , $moduleFolder=self::DEFAULT_MODULE)
	{
		$string = '<?php
namespace '.ucfirst($moduleFolder).'\Core\Acl\Resource ;	 
					
class '.ucfirst($acl_res).'
{
					
	const RESOURCE = \''.$acl_res.'\';						
						
	public function getResourceId() 
	{ 
		return self::RESOURCE ; 
	}
						
}';
		
		return $string ;
	}
}