<?php

namespace DV\Validator ;

use Symfony\Component\Validator\ConstraintValidator  as Laminas_Validate ;
use Symfony\Component\Validator\Constraint;


class CorrectSecurityAnswer extends Laminas_Validate
{ 
	
	public function validate($value, Constraint $constraint)
	{
		
	}
}