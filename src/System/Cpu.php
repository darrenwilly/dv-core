<?php
namespace DV\System ;

use DV\System\Cpu\CpuAbstract ;


class Cpu extends CpuAbstract
{
    /**
     * @var CpuResourceInterface
     */
    protected $adapter ;

	public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
    }
    public function getAdapter()
    {
        return $this->adapter ;
    }


    

}