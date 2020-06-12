<?php
declare(strict_types=1) ;

namespace DV\Model ;

use Veiw\Core\Query\System;

abstract class BaseAbstract extends DoctrineBaseAbstract
{
 	const DOCTRINE_ORM_EM = 'Doctrine\ORM\EntityManagerInterface' ;
	
	const YES = 'Y' ;

	public function getLookup(array $_options)
	{
		return $this->findBy($_options) ;
	}

	public function getSystem()
	{
        $sys_model = $this->container->get(System::class) ;
		return $sys_model ;
	}

}