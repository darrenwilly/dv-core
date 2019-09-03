<?php
namespace DV\Service ;
use Rhumsaa\Uuid\Uuid;

/**
	 * Generate a Unique String .
	 * 
	 * @param prefix for output String $type
	 * @param int the lenght of return string $length
	 * @param alpha the type of  character to return $chars
	 * 
	 * @return string.
	 */
class UniqueGen 
{
	const NUMERIC = '0123456789' ;
	const ALPHA_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' ;
	const ALPHA_LOWER = 'abcdefghijklmnopqrstuvwxyz' ;
    const ALNUM_UPPER_LOWER = self::NUMERIC.self::ALPHA_UPPER.self::ALPHA_LOWER ;
    const ALNUM_UPPER = self::NUMERIC.self::ALPHA_UPPER ;
    const ALNUM_LOWER = self::NUMERIC.self::ALPHA_LOWER ;

	/**
	 * return mixed generated letter string.
	 * 
	 * @param string $type
	 * @param integer $length
	 * @param string $chars
	 */
	private static function generate($type = null , $length = 30, $chars = self::ALNUM_UPPER_LOWER)
	{
		
		// Length of character list
		$chars_length = (strlen($chars) - 1);

		// Start our string
		$string = $chars{mt_rand(0, $chars_length)};

		// Generate random string
		for ($i = 1; $i < $length; $i = strlen($string))
		{
			// Grab a random character from our list
			$r = $chars{mt_rand(0, $chars_length)};

			// Make sure the same two characters don't appear next to each other
			if ($r != $string{$i - 1}) $string .=  $r;
		}
		
				// Return the string
		return $type . $string ;
	}


	/**
	 * return the generated string from Construct.
	 * @param null $type
	 * @param int $length
	 * @param string $chars
	 * @return string
	 */
	public static function printString($type = null , $length = 30, $chars = self::ALNUM_UPPER_LOWER)
	{
		return self::generate($type , $length, $chars);
	}

	
	/**
	 * return only generated letter string.
	 * 
	 * @param string $type
	 * @param integer $length
	 * @param string $chars
	 */
	public static function letterGen($type = null , $length = 6, $chars = self::ALPHA_UPPER)
	{
		return self::generate($type , $length , $chars);
	}

	
	/**
	 * return only generated letter string.
	 *  
	 * @param string $type
	 * @param integer $length
	 * @param string $chars
	 */
	public static function numberGen($type = null , $length = 10, $chars = self::NUMERIC)
	{
		return self::generate($type , $length , $chars);
	}

    public static function md5Generate()
    {
        $chars = md5(uniqid('', true));
        return substr($chars, 2, 2) . substr($chars, 12, 2) . substr($chars, 26, 2);
    }

    public static function __callStatic($name, $arguments)
    {
        if(method_exists(Uuid::class , $name))    {
            ##
            return Uuid::{$name}($arguments) ;
        }
    }
}