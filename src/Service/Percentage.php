<?php
namespace DV\Service ;
/**
 * Veiw_Service_Taxation
 * 
 * Provides tax calculation service
 * 
 * @category   DV
 * @package    Veiw_Service
 * @copyright  Copyright (c) 2013 Darrism Solution
 * @license        New BSD License
 */
class Percentage
{
    const VATRATE = 5 ;
    
    const HUNDRED = 100 ;
    
    const FIFTY = 50 ;
    
    const TWENTY = 20 ;
    
    
    /**
     * calculate to get the number figure of a percentage
     * e.g 1.5% of 3000 = 45
     * 
     * @param int $amount the amount
     * @param int $value_added default to vat
     * @return number
     */   
    static public function getPercentageFigure($amount , $value_added=self::VATRATE)
    {
        $real_figure_of_percentage =  bcmul(($value_added / self::HUNDRED) , $amount) ;
        
        return round($real_figure_of_percentage , 2) ;
    }
    
    
    /**
     * calculate to get the percentage figure of a number
     * e.g 45 of 3000 = 1.5% 
     * 
     * @param int $amount the amount
     * @param int $value_added default to vat
     * @return number
     */
    static public function getFigurePercentage($amount , $value_added=self::VATRATE)
    {
        $real_percentage_of_figure =   bcmul(($value_added / $amount) , self::HUNDRED) ;
        
        return round($real_percentage_of_figure , 2) ;
    }
    
  
}