<?php
namespace DV\Service ;

trait TraitOptions
{
    protected $options ;


    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions($options=[])
    {
        $this->options = $options;
    }
}