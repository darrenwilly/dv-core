<?php

namespace DV\Filters ;

class MaximumPercentage
{
	
	
	 
    public function filter($value)
    {
        ##$value = intval($value);        
        
        ### search for % and other unwanted items  in the value and replace it with emptyness
        $value = str_replace(array('%' , '~' , '!' , '#' , '$' , '^' , '&' , '*' , '(' , ')' , '_' , '+' , '=' , '-' , 
        					',' , '/' , '"' , '\'' , ';' , ':' , '{' , '}' , '[' , ']' , '|' , '\\' , '@'),
        					  '' , $value) ;
        					
        ### make sure the number is not greater than 100
        if($value > 100)	{
        	### return value as 100 if greater than 100
        	$value = 100 ;
        }   
             
        return $value;
    }
}
