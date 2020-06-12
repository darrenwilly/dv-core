<?php
namespace DV\System\Windows ;

use DV\System\CpuResourceInterface;
use Laminas\Config\Config;

class CpuResourceServiceAdapter implements CpuResourceInterface
{
    protected $connection ;

    public function __construct()
    {
        if (stristr(PHP_OS, 'win') && extension_loaded('com_dotnet'))	{
            ## call the windows management instrumentation command.
            $this->connection = $this->_COMConnect() ;
        }
    }

    public function connect()
    {

    }

    public function extract()
    {
        ### initiate a config engine
        $config = new Config([] , true) ;
        $config->core = [] ;

        $connection = $this->connection ;
        ### run the BIOS Pool Query
        $wmi_proc_pool  =  $connection->ExecQuery("Select * from Win32_Processor") ;
        ### start adding the necessary configuration
        $config->core->server = [];
        ### create the server processor key
        $config->core->server->processor = [] ;
        ### iterate through the processor info
        foreach($wmi_proc_pool as $proc_info)    {
            ### populate the processor info
            $config->core->server->processor->manufacturer = $proc_info->Manufacturer ;
            $config->core->server->processor->id = $proc_info->ProcessorId ;
            $config->core->server->processor->uniqueid = $proc_info->UniqueId ;
            $config->core->server->processor->name = $proc_info->Name ;
            $config->core->server->processor->cores = $proc_info->NumberOfCores ;
            $config->core->server->processor->family = $proc_info->Family ;
            $config->core->server->processor->architecture = $proc_info->Architecture ;
            $config->core->server->processor->description = $proc_info->Description ;
        }

        ### run the BIOS Pool Query
        $wmi_bios_pool  =  $connection->ExecQuery("Select * from Win32_BIOS") ;
        ### create the server bios tree
        $config->core->server->bios = [] ;
        ### iterate the bios pool
        foreach ($wmi_bios_pool as $bios_info)    {
            ### populate the bios info
            $config->core->server->bios->manufacturer = $bios_info->Manufacturer ;
            $config->core->server->bios->serial = $bios_info->SerialNumber ;
            $config->core->server->bios->description = $bios_info->Description ;
            $config->core->server->bios->version = $bios_info->Version ;
        }

        ### run the BIOS Pool Query
        $wmi_system_os  =  $connection->ExecQuery("Select * from Win32_OperatingSystem") ;
        ### create the OS key tree
        $config->core->server->os = [] ;
        ### iterate the system core os
        foreach($wmi_system_os as $server_os)    {
            ###
            $config->core->server->os->name = $server_os->Name ;
            $config->core->server->os->organisation = $server_os->Organization ;
            $config->core->server->os->architecture = $server_os->OSArchitecture ;
            $config->core->server->os->producttype = $server_os->ProductType ;
            $config->core->server->os->serialnumber = $server_os->SerialNumber ;
        }

        ##
        return $config;
    }


    protected function _COMConnect()
    {
        return new \COM("winmgmts://localhost/root/CIMV2") ;
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

}