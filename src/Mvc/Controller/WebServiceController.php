<?php
namespace DV\Mvc\Controller ;

use DV\Mvc\LogicResult;
use DV\Mvc\Response\LogicResultResponse;
use Zend\Http\Response;
use Zend\I18n\Validator\IsFloat;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;
use Zend\Validator\Callback;
use Zend\Validator\Digits;
use Zend\Validator\StringLength;

class WebServiceController extends ActionController
{
    protected $eventIdentifier = 'DV\Mvc\Controller\WebServiceController' ;

	protected $_error = array() ;

    protected function validate(array $options=[] , $returnBool = false)
    {
        $params = $this->getParameters() ;

        ###
        $input = new InputFilter();
        $input->setData($params);

        ### key is the urlparams to be validated
        foreach($options as $url_key => $value)   {
            ###
            $item = new Input($url_key) ;
            $item->setValue($params->{$url_key});

            ### break apart the value using | to determine the validators
            $validator_to_consider = explode("|" , $value) ;
            ###
            foreach($validator_to_consider as $validator)   {
                ###
                if($validator == 'required')    {
                    ###
                    $item->setRequired(true) ;
                }

                if(substr($validator,0,5) == 'limit')    {
                    ### break the content of limit & minMax value using -
                    list($limit , $minMax) = explode("-" , $validator) ;
                    ### fetch min and max using :
                    list($min , $max) = explode(":" , $minMax) ;
                    ### create a stringlength validator
                    $stringlength = new StringLength();
                    $stringlength->setEncoding('utf-8');
                    if(isset($min))    {
                        $stringlength->setMin($min) ;
                    }
                    if(isset($max))     {
                        $stringlength->setMax($max);
                    }
                    ###
                    $item->getValidatorChain()->attach($stringlength);
                }

                if($validator == 'digits')    {
                    $digit = new Digits();
                    $item->getValidatorChain()->attach($digit);
                }

                if($validator == 'float')    {
                    $float = new IsFloat();
                    $item->getValidatorChain()->attach($float);
                }

                if($validator == 'array')    {
                    ### use callable as validator
                    $is_array = function($value)  {
                        if(! is_array($value))    {
                            return false;
                        }
                        return true;
                    };
                    ### assign the callable to callback validator
                    $array_validator = new Callback($is_array) ;
                    $item->getValidatorChain()->attach($array_validator);
                }
                ### add the item to InputFilter
                $input->add($item) ;
            }

            ## validate the current iterated input validator
            if(! $input->isValid())    {
                ##
                return new LogicResult($input) ;
            }
        }

        if(true === $returnBool)    {
            return true;
        }
        return new LogicResult(['status' => 200]) ;
    }

}