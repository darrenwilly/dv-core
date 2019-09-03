<?php
namespace DV\System\Cpu ;

 
abstract class CpuAbstract 
{
	const WINDOWS = 'WINDOWS' ;
	
	const LINUX = 'LINUX' ;
	
	const UNIX = 'UNIX' ;
	
	protected $wmi  ;
	
	
	
	public function __construct($_options=[])
	{
		$this->wmi = $this->WMI() ;
	}
	
	
	/**
	 *  sys_getloadavg equivalent for Windows OS
	 *  Returns samples representing the average system load (the number of processes in the system run queue) 
	 *  over the last 1, 5 and 15 minutes, respectively.
	 *  
	 *  @return Header showing buzy page
	 * 
	 */
	public function windows_OS_Resource_Passthru()
	{
		### output buffering start
		ob_start();
		
		passthru('typeperf -sc 1 "\processor(_total)\% processor time"' , $status);
		
		$content = ob_get_contents();
		
		### output buffering end
		ob_end_clean();
		
		if ($status === 0) {
		    if (preg_match("/\,\"([0-9]+\.[0-9]+)\"/", $content, $load)) {
		        if ($load[1] > get_config('busy_error')) {
		            header('HTTP/1.1 503 Too busy, try again later');
		            die('Server too busy. Please try again later.');
		        }
		    }
		}
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
    
    
    public function win32Processor()
    {
    	$server = $this->wmi->ExecQuery("SELECT * FROM Win32_Processor");
    	return $server ;
    }
    
    
    private function WMI()
    {
    	if (stristr(PHP_OS, 'win') && extension_loaded('com_dotnet'))	{ 
       		### call the windows management instrumentation command.
            $wmi = new \COM("Winmgmts://") ;            
    	}
    	
    	return $wmi ;
    }
}