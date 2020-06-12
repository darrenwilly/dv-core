<?php
namespace DV\Service ;

use DateTime ;
use DV\Service\Image\Scale;
use Laminas\I18n\Filter\Alnum;

/**
 * Handle File Upload, Renaming, Copy, Filter and other Manipulations
 * 
 * @author Darrism Solution
 * @copyright Darrism Solutions Ltd 2012
 * 
 * @version 1.1.0
 *
 */
trait FileManager
{
	use Scale ;

	protected $_options = array() ;
	
	protected $_adapter ;
	
	protected $_validators = array() ;
	
	protected $_filters = array() ;
	
	protected $_form ;
	
	protected $_destination ;


	/**
	 * Handle file upload
	 *
	 * @param array $_options
	 * @return array|bool
	 * @throws \Exception
	 * @internal param array|\Laminas\Config $options
	 * @internal param array $data
	 */
	protected function upload(array &$_options)
	{		    		
		$file_options = $_options ;
		$file_options['uniqueId'] = uniqid(null , true) ;
		$file_options['name'] = $_options['name'] ;
		$file_options['mimeType'] = $_options['type'] ;
		$file_options['fileExtension'] = ltrim(strrchr($_options['name'], '.') , '.');
		$file_options['size'] = filesize($_options['tmp_name']) ;
		$file_options['md5'] = md5_file($_options['tmp_name']) ;
		$file_options['sha1'] = sha1_file($_options['tmp_name']) ;
		
		if(isset($_options['useDbAsBinary']))	{
            $file_options['file'] = file_get_contents($_options['tmp_name']) ;
		}

        $app_path = APPLICATION_PATH ;
        if(isset($_options['noAppPath']))    {
            ## meaning that the directory has the app path already prepended
            $app_path = null ;
        }

        ### check for the directory to keep the file and create if not available
        if(! file_exists($app_path . $_options['directory']))	{
            ### create the directory
            self::zfMVCFolder($app_path . $_options['directory']) ;
        }

        ### fix that autodelete previous file in the directory
        if(isset($_options['autoRemoveExist']))	{
            ### delete any previous bioPhoto
            self::removeFile($app_path . $_options['directory']) ;
        }

        ### fix that autodelete previous file in the directory
        if(isset($_options['autoRename']))	{
            ### delete any previous bioPhoto
            $now = new DateTime;
            ### use the Alnum filter
            $filter = new Alnum() ;
            ### strip file extension from the original filename
            $name_without_ext = $filter->filter($_options['name'] );
            ### make sure the inital filename is not more than 15
            if(strlen($name_without_ext) > 15)    {
                $name_without_ext = substr($name_without_ext , 0 , 15) ;
            }
            ### create a new name with current time appended
            $new_name_with_time = $name_without_ext . $now->format('Y_m_d_h_i_s_u');#. $filter->filter(microtime());
            ### overwrite the initial filename
            $_options['name'] = $new_name_with_time .'.'.$file_options['fileExtension'];
        }

        ###
        $new_file_name_destn = $app_path . $_options['directory'] . DIRECTORY_SEPARATOR . $_options['name'];

        if(isset($_options['moveUploaded'])) {
            ### check if the new destination is available else throw exception
            if(! file_exists($app_path . $_options['directory']))	{
                ### create the directory
                self::zfMVCFolder($app_path . $_options['directory']) ;
            }
            ### move the file from temp to d new directory
            $move = move_uploaded_file($_options['tmp_name'], $new_file_name_destn);
            ### change the mode of the file to nobody
            chmod($new_file_name_destn, 0777);
            ### confirm file movement
            if(! $move)	{
                throw new \Exception('unable to move uploaded file to the new location: "'. $app_path . $_options['directory']. '"') ;
                return false ;
            }
        }

        if(isset($_options['autoScaleImage'])) {
            if (in_array($_options['mimeType'], ['image/jpeg', 'image/jpg', 'image/png'])) {
                ### process the file if it is image
                $this->scaleImageFileToBlob($new_file_name_destn , $new_file_name_destn);
            }
        }

        $file_options['name'] = $_options['name'] ;
        $file_options['newFileName'] = $new_file_name_destn ;
        $file_options['createdAt'] = new \DateTime ;
        $file_options['activated'] = ActionControl::YES ;
						 
		## delete the temp file that was originally uploaded.
		if(file_exists($_options['tmp_name']))	{
			unlink($_options['tmp_name'])  ;
		}

        ### make sure that options will also return the same value as fileoptions
        $_options = array_merge($_options , $file_options) ;
				
		return $file_options ;
	}
	
	
	/**
	 * Proxy PHP Internal COPY Function
	 * 
	 * @param string $source Directory from
	 * @param string $destn Directory to
	 */
	static public function copy($source , $destn)
	{
		## copy the new upload document from temporary folder to the user mail folder
		return copy($source , $destn);
	}


	/**
	 * Proxy PHP Internal RENAME Function
	 *
	 * @param $oldName
	 * @param $newName
	 * @return bool
	 * @internal param string $source Directory from
	 * @internal param string $destn Directory to
	 */
	static public function rename($oldName , $newName)
	{
		## copy the new upload document from temporary folder to the user mail folder
		return rename($oldName  , $newName);
	}


	/**
	 * Create a folder into a directory on a static call
	 *
	 * @param string $docDir
	 * @param int $mode
	 * @throws \Exception
	 */
	public static function zfMVCFolder($docDir , $mode=0770)
	{
		if(null != $docDir)	{
			
			if(! @is_dir($docDir) && ! @opendir($docDir))		{
				## create the folder
				if(! mkdir($docDir , $mode , true))		{				
					throw new \Exception('Unable to create dir "'.$docDir.'" as requested') ;
				}
                chmod($docDir , $mode) ;
			}
			
		}else	{
			throw new \Exception('Unable to locate nor create dir "'.$docDir.'"') ;
		}
	}
	
	/**
	 * Remove file from a given directory name 
	 * 
	 * @param string $directory
	 * @return bool
	 */
	static public function removeFile($directory)
	{
		if(! @opendir($directory))	{
			return false ;
		}
		###
		$dir = new \DirectoryIterator($directory) ;		
		###
		foreach ($dir as $fileinfo) {
			###
			if ($fileinfo->isFile()) {
				### remove the file
				@unlink($fileinfo->getPathname()) ;
			}
		}
		return true;
	}

}