<?php
namespace DV\System\Linux ;

use DV\System\CpuResourceInterface;
use Laminas\Config\Config;
use Symfony\Component\Process\Process;

class CpuResourceServiceAdapter implements CpuResourceInterface
{
    protected $connection ;
    protected $processRunner ;

    public function __construct()
    {
    }

    public function connect()
    {
        ##sudo dmidecode -t system
    }

    public function extract()
    {
        ### initiate a config engine
        $config = new Config([] , true) ;
        $config->core = [] ;

        $connection = $this->processRunner ;

        ### run the BIOS Pool Query
        $wmi_proc_pool  = new Process(['dmidecode' , '-t system']) ;

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
}