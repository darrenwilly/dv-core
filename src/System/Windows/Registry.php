<?php
namespace DV\System\Windows ;

class Registry
{
    protected $_com ;
    
    
    public function __construct()
    {
        
    }
    
    
    public function registry_write($folder, $key, $value, $type="REG_SZ")
    {
    	$WshShell = new COM("WScript.Shell");
    
    	$registry = "HKEY_LOCAL_MACHINE\\SOFTWARE\\" . $folder . "\\" . $key ;
    	
    	try{
    		$result = $WshShell->RegWrite($registry, $value, $type);
    		echo "Entry is Successfully written at:".$registry;
    		return($result);
    	}
    	catch(Exception $e){
    		echo "Some Exception in Registry writing".$e;
    	}
    
    	return false;
    }    
    
    
    public function registry_delete($folder, $key, $value, $type="REG_SZ")
    {
    	$WshShell = new COM("Wscript.shell");
    	$registry = "HKEY_LOCAL_MACHINE\\SOFTWARE\\" . $folder . "\\" . $key;
    	try{
    		$result = $WshShell->RegDelete($registry);
    		echo $key." is Successfully deleted from HKEY_LOCAL_MACHINE\\SOFTWARE\\" . $folder ;
    		return($result);
    	}
    	catch(Exception $e){
    		echo "Some Exception with the code::".$e;
    	}
    	return false;
    
    }
    
    
}