<?php
namespace DV\Service\Image;


trait Base64Processor
{

    /**
     * convert an employee image to base64 without view helper
     *
     * @param $employee
     * @return string
     * @throws \Exception
     */
    public function toB64Uri($options)
    {
        if(! isset($options['filename']))    {
            throw new \InvalidArgumentException('Filename to use for the stream is not found') ;
        }

        $filename = $options['filename'] ;
        ##
        if(! file_exists($filename))    {
             throw new \UnexpectedValueException('File string provided is not valid') ;
        }
        ##
        $mime = mime_content_type($filename);
        $img_file = base64_encode(file_get_contents($filename));
        ##
        return "data:$mime;base64,$img_file" ;
    }

    public function fromB64($options)
    {

    }
}