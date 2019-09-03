<?php
namespace DV\System ;

use DV\System\Cpu\CpuAbstract ;


class Cpu extends CpuAbstract
{
	
	public function __construct($_options=[])
	{
		$this->wmi = $this->wmi() ;	
	}
	
	/**
	 *  sys_getloadavg equivalent for Windows OS
	 *  Returns samples representing the average system load (the number of processes in the system run queue) 
	 *  over the last 1, 5 and 15 minutes, respectively.
	 *  
	 *  @Note that this method is not fast, so be careful in the number of calls to this function.
	 *  
	 *  @return Header showing buzy page
	 * 
	 */
	public function get_server_load() 
	{   
        if (stristr(PHP_OS, 'win')) {
            ### run a WMI query language on Win32_Processor Engine
            $server = $this->wmi->ExecQuery("SELECT LoadPercentage FROM Win32_Processor");
           	### represent number of cpu resources number
            $cpu_num = 0;
            ### represent number of total load
            $load_total = 0;
           	### iterate through the result returned from WMI query
            foreach($server as $cpu){
            	### increment the CPU resources variable
                $cpu_num++ ;
                ### load the percentage of CPU Resouces and increment it throughout the iteration
                $load_total += $cpu->loadpercentage;
            }
            ## round the division of CPU_LOAD and CPU_NUM
            $load = round($load_total/$cpu_num);
           
        } else {
       
            $sys_load = sys_getloadavg();
            $load = $sys_load[0];
       
        }
       
        return (int) $load;
   
    }
    
    /**
     * Fetch for the all the property of Windows processor.
     * 
     * @return Win32_Processor WMI class represents a device that can interpret a sequence of instructions on a 
     * 			computer running on a Windows operating system. On a multiprocessor computer, one instance of 
     * 			the Win32_Processor class exists for each processor
     */
    public function win32Processor()
    {
    	$server = $this->wmi->ExecQuery("SELECT * FROM Win32_Processor");
    	return $server ;
    }
    
    
    public function wmi()
    {
    	if (stristr(PHP_OS, 'win') && extension_loaded('com_dotnet'))	{ 
       		### call the windows management instrumentation command.
            $wmi = new \COM("winmgmts://localhost/root/CIMV2") ; 	   
    	}
    	
    	return $wmi ;
    }    
    
}