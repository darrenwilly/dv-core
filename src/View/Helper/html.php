<?php
namespace DV\View\Helper ;

use DV\View\Base as dv_base ;
use Zend\I18n\Filter\Alnum ;
use DV\Mvc\Service\ServiceLocatorFactory ;



class Html extends dv_base
{	
	
	public function __invoke()
	{
		return $this ;
	}


	/**
	 * Encode a file in url data format
	 *
	 * @param array $_options
	 * @return string
	 * @internal param string $file
	 * @internal param Type $Mime $mime
	 */
	public function dataUri($_options=[]) 
	{
		if(! isset($_options['file']) && ! isset($_options['binary']))	{
			die('filename / binary file is not set');
		}
		
		### filter the filename or alternative binary name
		$filter = new Alnum(false) ;
		
		### prepare file as hased
		if(isset($_options['file']))		{
			### fetch the name assigned to the document
			$filename = $_options['file']['name'] ;
			
			### check if directory is add or it just file name
			if(! isset($_options['file']['dir']))	{
				### add the document root
				$filename = realpath(APPLICATION_PATH.'/../public/'. $filename)  ;
			}
			else{
				### add the document root
				$filename = $_options['file']['dir'] .DIRECTORY_SEPARATOR. $filename ;
			}			

			##prepare name to use for cache
			$cacheable_name = $filter->filter(basename($filename)) ;
			
			### prepare content for a file 
			$contents = @file_get_contents($filename) ;
		}

		### apply a default mime
		if(! isset($_options['mime']))	{
			$_options['mime'] = 'image/jpg' ;
		}		
		$mime = $_options['mime'] ;			
	    	    
	    ### cache the url data info on production environment
	    if(PRODUCTION == APPLICATION_ENV)	{
            $result_bool = null;
	    	###
	    	$data_uri = $this->_getCache()->getItem($cacheable_name , $result_bool) ;
	    	### check for the available cache
	    	if(! ($result_bool)) 	{
	    		### base 64 encode the value if no cache was available
	    		$data_uri = base64_encode($contents) ;  
	    		### save the cache string. 	
	    		$this->_getCache()->setItem($cacheable_name , $data_uri);
	    	}  	
	    } else{
	    	### base 64 encode the value
	    	$data_uri = base64_encode($contents) ;
	    }    
	    
	    return "data:$mime;base64,$data_uri" ;	
	}

    public function dataUri64($options)
    {
        ### check if the content of binary is set
        if(! isset($options['content'])) {
            die('unable to detect the binary content');
        }

        ### fetch the content of the file
        $contents = $options['content'] ;

        if(! isset($options['mime']))	{
            $options['mime'] = 'image/jpg' ;
        }
        $mime = $options['mime'] ;

        return "data:$mime;base64,$contents" ;
    }
	
	/**
	 * Fetch the cache engine
	 * 
	 * @return Zend\Cache\Storage\StorageInterface
	 */
	protected function _getCache()
	{
	    ### create the option configuration to pass unto cache
		$_cache = ServiceLocatorFactory::getLocator('DV\View\Helper\html\Cache') ;
		return $_cache ;
	}
}