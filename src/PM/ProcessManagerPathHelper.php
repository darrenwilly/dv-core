<?php
declare(strict_types=1);

namespace DV\PM;

use DirectoryIterator ;
#use function is_dir ;

trait ProcessManagerPathHelper
{

    static public function embeddedDirLoader()
    {
        $process_manager_path_embedded = '' ;

        ## when the constant that define where the PM should be loaded is not defined, then use default
        if(! defined('PROCESS_MANAGER_PATH_EMBEDDED'))    {
            $process_manager_path_embedded = realpath(dirname(dirname(dirname(dirname(__DIR__))))).DIRECTORY_SEPARATOR.'pm';
        }else{
            $process_manager_path_embedded = PROCESS_MANAGER_PATH_EMBEDDED ;
        }

        ## make sure that the folder exist
        if(null == $process_manager_path_embedded)    {
            ##
            return ;
        }
        ## load the module directory
        $installed_pm_dir = new DirectoryIterator($process_manager_path_embedded) ;

        ## make sure that module folder is not iterated when empty
        if(null == $installed_pm_dir)    {
            return ;
        }
        ##
        return $installed_pm_dir ;
    }

}