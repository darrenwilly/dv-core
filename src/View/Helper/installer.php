<?php
namespace DV\View\Helper ;

use DV\View\Base ;


class installer extends Base
{
  
    /**
     */
    public function __invoke()
    { 
        return $this;
    }
    
    
    public function restart_installation()
    {
    	$html = "<a href='".$this->view->url('installer' , ['controller' => 'installer' , 'action' => 'restart'] 
	        	      )
    				."' class='active_db'>Restart Installer</a>" ;
		return $html ;
    }
    
    
    public function get_core_file()
    {
        $core_file_dir = realpath(APPLICATION_PATH . '/../data/install/core.ini') ;
        
        $html = '' ;
        
        if(file_exists($core_file_dir))	{
        	###
        	$core_file_dir_spl = new \SplFileObject($core_file_dir , 'r+') ;
        	
        	###
	        $html .= '<li>'. $core_file_dir_spl->getFilename() . '<br>' ;
	        
	        
	        $html .= '<a class="btn btn-violet" href="'.$this->view->url('installer' , ['controller' => 'installer' , 'action' => 'generate-config-file'],
	        	                            ['query' => ['task' => 'download' , 'file' =>  $core_file_dir_spl->getFilename()]
	        	      ]).'">Download</a><br />' ;
	        			    
	        $html .= '<a class="btn btn-violet" href="'.$this->view->url('installer' , ['controller' => 'installer' , 'action' => 'generate-config-file'],
	        	                            ['query' => ['task' => 'mail' , 'file' =>  $core_file_dir_spl->getFilename()
	        	     ]]).'">Mail to Vendor</a>' ;
	        
        }	
        
       $html .= '</li>' ;        			
        			
       $html = vsprintf('<ol class="active_db"> 
                            %s 
                        </ol>', [$html]) ;
        
        return $html ;
    }
}
