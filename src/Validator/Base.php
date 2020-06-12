<?php
namespace DV\Validator;

use DV\MicroService\TraitContainer;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\ConstraintValidator as Laminas_validate ;

abstract class Base extends Laminas_validate
{
    use TraitContainer ;

	public function __construct($options=[])
	{
		parent::__construct($options) ;

        if(isset($options['container']) && $options['container'] instanceof ContainerInterface)    {
            ##
            $this->setContainer($options['container']) ;
        }
	}
	

}