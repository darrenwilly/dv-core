<?php

namespace DV\Validator ;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator as Laminas_Validate ;

class SamePassword extends Laminas_Validate
{
    const NOT_MATCH = 'notMatch';

    protected $messageTemplates = array(
        self::NOT_MATCH => 'Passwords do not match'
    );

    public function validate($value, Constraint $constraint)
    {
        $value = (string) $value;

        if (is_array($context)) {
            if (isset($context['NewPwd'])
                && ($value == $context['NewPwd']))
            {
                return true;
            }
        } elseif (is_string($context) && ($value == $context)) {
            return true;
        }

        $this->_error(self::NOT_MATCH);
        return false;
    }
}