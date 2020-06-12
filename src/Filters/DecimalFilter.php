<?php

namespace DV\Filters ;


class DecimalFilter
{
	
	
	 
    public function filter($value)
    {
        $find    = array(',');
        $replace = array('');
        $new = str_replace($find, $replace, $value);

        return $new;
    }
}
