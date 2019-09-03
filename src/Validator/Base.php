<?php
namespace DV\Validator;

use DV\MicroService\TraitContainer;
use Zend\Validator\AbstractValidator as zend_validate ;

abstract class Base extends zend_validate
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