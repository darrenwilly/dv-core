<?php

namespace DV\Validator ;

use Zend\Validator\AbstractValidator as Zend_Validate ;



class SamePassword extends Zend_Validate
{
    const NOT_MATCH = 'notMatch';

    protected $messageTemplates = array(
        self::NOT_MATCH => 'Passwords do not match'
    );

 
    
    public function isValid($value, $context = null)
    {
        //$value = (string) $value;
        $this->_setValue($value);

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