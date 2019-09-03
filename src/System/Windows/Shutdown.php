<?php
namespace DV\System\Windows ;

use DV\System\Cpu\CpuAbstract ;

class Shutdown extends CpuAbstract
{
    
    public function init()
    {
        $this->wmi = $this->wmi() ;    
    }
    
    
    public function execute()
    {
        $win32_os = $this->win32OperatingSystem() ;
        
        foreach ($win32_os as $win32_os_processes)    {
            $win32_os_processes->Shutdown() ;
        }
        
        return true ;
    }
    
    /**
     * Fetch for the all the property of Windows processor.
     *
     * @return Win32_Processor WMI class represents a device that can interpret a sequence of instructions on a
     * 			computer running on a Windows operating system. On a multiprocessor computer, one instance of
     * 			the Win32_Processor class exists for each processor
     */
    public function win32OperatingSystem()
    {
    	$server = $this->wmi->ExecQuery("select * from Win32_OperatingSystem where Primary=true");
    	return $server ;
    }
    
    public function wmi()
    {
    	if (stristr(PHP_OS, 'win') && extension_loaded('com_dotnet'))	{
    		### call the windows management instrumentation command.
    		#$wmi = new COM("winmgmts://localhost/root/CIMV2") ;    		
    		#$wmi = new COM("winmgmts:{(Debug,RemoteShutdown)}//REMOTE_SYSTEM_NAME/root/cimv2") ;
    		$wmi = new COM("winmgmts:{(Shutdown)}//./root/cimv2") ;
    	}
    		 
    	return $wmi ;
    }
}