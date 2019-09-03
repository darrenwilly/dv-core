<?php
namespace DV\Barcode\QR ;

 /**
 * PHP QR Code encoder
 *
 * Exemplatory usage
 *
 * PHP QR Code is distributed under LGPL 3
 * Copyright (C) 2012 Darrism Solution Limited
 *
 **/
class Qrgen
{
	
	const DEFAULT_ZF_DIR = '/../data/document/qrcode' ;
	
	const ECC_LEVEL_L = 'L' ;
	
	const ECC_LEVEL_M = 'M' ;
	
	const ECC_LEVEL_Q = 'Q' ;
	
	const ECC_LEVEL_H = 'H' ;
	
	/*
	 * Hold all the ECC level.
	 */
	protected $_ecc_level = array(self::ECC_LEVEL_L , self::ECC_LEVEL_M , self::ECC_LEVEL_Q , self::ECC_LEVEL_H) ;
	
	protected $_options = array() ;
	
	protected $_dir ;
	

		/**
		 * Constructor initiate the library and save the QR code image file.
		 * 
		 * @param array $options configuration for the code
		 */
		public function __construct($options=array())
		{
			### fetch the QR dependency lib.
			if(! $this->_getDependencyLibrary())	{
				### throw an exception.
				throw new \Exception('unable to initiate the required library') ;
			}	

			if(! is_array($options))	{
				### throw an exception.
				throw new \Exception('value must be an array') ;
			}
			
			$this->_options = $options ; 
		}


	/**
	 * Constructor initiate the library and save the QR code image file.
	 *
	 * @param string $data Value to print
	 * @param string $filename name to use for saving the image file
	 * @param string|null $dir default to null or specify a location
	 * @param string $errorCorrectionLevel ECC level to use
	 * @param int $matrixPointSize the pixel size of the drawing
	 * @param int $margin
	 * @return bool
	 * @throws DV_Barcode_QR_Exception
	 */
		public function Draw($data=null , $filename=null , $dir=null , $errorCorrectionLevel = self::ECC_LEVEL_L  , 
								$matrixPointSize=4 , $margin=2)
		{
			if(is_array($this->_options) && count($this->_options) >= 3)	{
				### extract all the keys in the options as variable
				extract($this->_options) ;
			}
			
			if(null == $data)	{
				### throw an exception.
				throw new \UnexpectedValueException('Data to generate is not available') ;
			}
			
			### make sure that $filename value ends with png
			if(substr($filename , -0 , 3) != '.png')	{
				$filename = $filename . '.png' ;
			}
			
			### check for null on the $dir(external folder to include) .
			if(null != $dir)	{
				### merge the folder along with the filename
				$filename = $dir . '/' . basename($filename) ;
			}else{
				$filename = $dir . '/' . basename($filename) ;
			}			
			
			### check and make sure that the ECC is set and matchup with the define one
			if (!in_array($errorCorrectionLevel , $this->_ecc_level))	{
				### throw an exception.
				throw new \UnexpectedValueException('error correction level is not set') ;
			}			
						
			### check and configure the matrix point
			if(is_numeric($matrixPointSize))	{
				$matrixPointSize = min(max((int)$matrixPointSize , 1) , 10) ;
			}
			
			if(file_exists($filename))    {
			    return true ;
			}
			
			### return a draw QR barcode file.
			if(QRcode::png($data , $filename , $errorCorrectionLevel , $matrixPointSize , $margin))    {
			    return true ;
			}   
			
			return false ;
		}
		
		
		/**
		 * set the QR code directory where the Barcode image will be generated.
		 * 
		 * @param string $dir
		 */
		public function setDefaultDir($dir=self::DEFAULT_ZF_DIR)
		{
			$this->_dir = APPLICATION_PATH . $dir ;
		}
		
		
		/**
		 * get the QR code directory where the Barcode image was generated.
		 * 
		 */
		public function getDefaultDir()
		{
			return $this->_dir ;
		}
		
		/**
		 * Fetch the QR library from the original QR library.
		 * 
		 */
		private function _getDependencyLibrary()
		{
			return require_once __DIR__.DIRECTORY_SEPARATOR.'qrlib.php' ;
		}
		
}