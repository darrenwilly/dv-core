<?php

namespace DV\Validator ;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator as Laminas_Validate ;


class PasswordVerification extends Laminas_Validate
{
	public function validate($value, Constraint $constraint)
	{
		
	}	
}