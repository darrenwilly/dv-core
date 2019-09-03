<?php
namespace DV\Service\Image;


trait Scale
{
    protected $maxWidth = 600 ;
    protected $maxHeight = 600 ;

    protected $outputDPIQuality = 300 ;

    public function scaleImageFileToBlob($file , $new_dstn)
    {
        if(! file_exists($file))    {
            return false;
        }
        #$source_pic = $file;
        $max_width = $this->getMaxWidth();
        $max_height = $this->getMaxHeight();

        list($width, $height, $image_type) = getimagesize($file);

        switch ($image_type)
        {
            case 1: $src = imagecreatefromgif($file); break;
            case 2: $src = imagecreatefromjpeg($file);  break;
            case 3: $src = imagecreatefrompng($file); break;
            default: return '';  break;
        }

        $x_ratio = $max_width / $width;
        $y_ratio = $max_height / $height;

        if( ($width <= $max_width) && ($height <= $max_height) ){
            $tn_width = $width;
            $tn_height = $height;
        }elseif (($x_ratio * $height) < $max_height){
            $tn_height = ceil($x_ratio * $height);
            $tn_width = $max_width;
        }else{
            $tn_width = ceil($y_ratio * $width);
            $tn_height = $max_height;
        }

        $tmp = imagecreatetruecolor($tn_width,$tn_height);

        /* Check if this image is PNG or GIF to preserve its transparency */
        if(($image_type == 1) OR ($image_type==3))
        {
            imagealphablending($tmp, false);
            imagesavealpha($tmp,true);
            $transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
            imagefilledrectangle($tmp, 0, 0, $tn_width, $tn_height, $transparent);
        }

        imagecopyresampled($tmp,$src,0,0,0,0,$tn_width, $tn_height,$width,$height);

        ### delete previous
        if(file_exists($new_dstn))	{
            unlink($new_dstn) ;
        }
        /* switch ($image_type)
        {
            case 1: imagegif($tmp , $new_dstn); break;
            case 2: imagejpeg($tmp, $new_dstn, 100);  break; // best quality
            case 3: imagepng($tmp, $new_dstn, 0); break; // no compression
            default: echo ''; break;
        } */
        imagejpeg($tmp, $new_dstn, $this->getDPIQuality());

        register_shutdown_function(function () use($file)   {
            @chmod($file , 0770) ;
            @unlink($file) ;
        }) ;

        return true ;
    }

    public function setMaxWidth($maxWidth)
    {
        $this->maxWidth  = $maxWidth ;
    }
    public function getMaxWidth()
    {
        return $this->maxWidth ;
    }

    public function setMaxHeight($maxHeight)
    {
        $this->maxHeight  = $maxHeight ;
    }
    public function getMaxHeight()
    {
        return $this->maxHeight ;
    }

    public function setDPIQuality($outputDPIQuality)
    {
        $this->outputDPIQuality  = $outputDPIQuality;
    }
    public function getDPIQuality()
    {
        return $this->outputDPIQuality ;
    }
}