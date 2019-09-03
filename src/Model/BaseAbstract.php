<?php
declare(strict_types=1) ;

namespace DV\Model ;

abstract class BaseAbstract extends DoctrineBaseAbstract
{
 	const DOCTRINE_ORM_EM = 'Doctrine\ORM\EntityManager' ;
	
	const YES = 'Y' ;

	public function getLookup(array $_options)
	{
		return $this->findBy($_options) ;
	}

	public function getSystem()
	{
        $sys_model = new \DV\Model\System() ;
		return $sys_model ;
	}

}