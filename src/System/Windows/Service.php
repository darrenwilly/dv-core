<?php
namespace DV\System\Windows;

use DV\System\SystemAbstract;
use COM ;
use Exception ;


/**
 * This class is designed to install a designed Windows Service that was created using VisualStudio
 *
 * Class Service
 * @package DV\System\Windows
 */
class Service extends SystemAbstract
{
    const OWN_PROCESS = 16 ;
    const NOT_INTERACTIVE = true ;
    const AUTOMATIC = 'Automatic' ;

    public function init()
    {
        try {
            if (stristr(PHP_OS, 'win') && extension_loaded('com_dotnet')) {
                ### call the windows management instrumentation command.
                $wmi = new COM("winmgmts://localhost/root/CIMV2");
            }

            $this->wmi = $wmi;
        } catch(\Exception $ex)   {
            throw $ex ;
        }
        return $wmi ;
    }

    public function InstallWindowsService($_options)
    {
        if(! isset($_options['name']))    {
            throw new Exception("service name is not provided") ;
        }

        if(! isset($_options['displayName']))    {
            throw new Exception("service display name is not provided") ;
        }

        if(! isset($_options['binPath']))    {
            throw new Exception("service path to executable is not provided is not provided") ;
        }

        extract($_options) ;

        try {
            $baseServiceObject = $this->win32BaseService();
            ###
            $create_service = $baseServiceObject->Create($name, $displayName, $binPath, self::OWN_PROCESS, 2, self::AUTOMATIC, false, null);

            if (0 < $create_service) {
                throw new Exception("we are unable to install $name service. please contact support");
            }
        }catch (\Exception $ex) {
            throw new \Exception('COM Object failed to create service object on windows platform : '. $ex->getMessage()) ;
        }
        return true ;
    }

    /**
     * Fetch for the all the property of Windows Base Service.
     *
     *
     * @return Win32_BaseService
     */
    public function win32BaseService()
    {
        ### fetch the  base class or
        #$baseServiceObject = $this->wmi->ExecQuery("SELECT * FROM Win32_BaseService");
        ### I will need to confirm what option will work
        $baseServiceObject = $this->wmi->Get("Win32_BaseService") ;
        ### any one of the above method will return the right class
        return $baseServiceObject ;
    }

}