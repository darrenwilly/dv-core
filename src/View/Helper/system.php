<?php
namespace DV\View\Helper ;

use DV\View\Base ;


class system extends Base
{
  
    /**
     */
    public function __invoke()
    { 
        return $this;
    }
    
    
    public function get_backup_file($task='download')
    {
        $backup_file_dir = realpath(APPLICATION_PATH . '/../data/backup/db') ;
        
        $img_dir_iterator = new \DirectoryIterator($backup_file_dir) ;
        
        $html = '' ;
        
        foreach($img_dir_iterator as $dir_iterator)    {
        	if($dir_iterator->isFile())    {
        		if(in_array($dir_iterator->getExtension() , array('sql')))    {
        			$html .= '<li>'. $dir_iterator->getFilename() . '<br>' ;
        			if($task == 'download')    {
        			    $html .= '<a class="btn btn-violet" href="'.$this->view->url('installer' , ['controller' => 'installer' , 'action' => 'install-db'],
        	                                                                ['query' => ['task' => 'download' , 'file' =>  $dir_iterator->getFilename()]
        	                        ]).'">Download</a>' ;
        			    
        			    $html .= '<a class="btn btn-violet" href="'.$this->view->url('installer' , ['controller' => 'installer' , 'action' => 'install-db'],
        	                                                                ['query' => ['task' => 'delete' , 'file' =>  $dir_iterator->getFilename()
        	                        ]]).'">Delete</a>' ;
        			}   
        			else{
        			    $html .= '<a class="btn btn-violet" href="'.$this->view->url('installer' , ['controller' => 'installer' , 'action' => 'install-db'] ,
        			    													['query' => ['task' => 'restore' , 'file' =>  $dir_iterator->getFilename()]
        			    ]).'">Restore</a>' ;
        			} 
        			$html .= '</li>' ;        			
        		}
        	}        		
        }
        
        $html = vsprintf('<ol class="eft_list"> 
                            %s 
                        </ol>', array($html)) ;
        
        return $html ;
    }
}
