<?php
namespace DV ;

trait TraitApplication
{
    protected $application ;


    public function getApplication()
    {
        return $this->application;
    }

    public function setApplication($application=[])
    {
        $this->application = $application;
    }
}