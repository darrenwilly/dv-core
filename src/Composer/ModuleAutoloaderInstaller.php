<?php
declare(strict_types=1);

namespace DV\Composer;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Composer\EventDispatcher\Event as DispatchEvent ;
use DV\PM\ProcessManagerPathHelper;

class ModuleAutoloaderInstaller
{
    use ProcessManagerPathHelper ;

    /**
     * during ComposerInit, check for process manager and autoload it
     * @param DispatchEvent $event
     *
     */
    public static function checkForProcessManager(DispatchEvent $event)
    {
        ## check if embedeed PM is available load the composer json
        /*if($pmDirIterator = self::embeddedDirLoader())   {
            ## load the composer json
            $rootComposerJsonFile = realpath(dirname(dirname(dirname(dirname(__DIR__)))).'/composer.json');
            ##
            if(file_exists($rootComposerJsonFile))    {
                ##
                $composerJsonObject = json_decode(file_get_contents($rootComposerJsonFile) , true) ;
                ##
                $composerAutoload = &$composerJsonObject['autoload'] ;

                ##check if the process manager exist
                if (! isset($composerAutoload['psr-4']['ProcessManager\\']))   {
                    ###
                    $composerAutoload['psr-4']['ProcessManager\\'] = dirname(dirname(dirname(dirname(__DIR__)))).'/pm/src' ;
                    ##
                    file_put_contents($rootComposerJsonFile , json_encode($composerJsonObject));
                }
            }

        }*/
    }

    public static function postUpdate(Event $event)
    {
    }

    public static function postAutoloadDump(Event $event)
    {
        $vendorDir = $event->getComposer()->getAutoloadGenerator();

    }

    public static function preAutoloadDump(Event $event)
    {
        $vendorDir = $event->getComposer()->getAutoloadGenerator();

    }

    public static function postPackageInstall(PackageEvent $event)
    {
        $installedPackage = $event->getOperation()->getPackage();
        // do stuff
    }

    public static function warmCache(Event $event)
    {
        // make cache toasty
    }

}