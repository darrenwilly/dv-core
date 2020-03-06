<?php
declare(strict_types=1);

namespace DV\Mvc\Controller ;

use DV\ContainerService\ServiceLocatorFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as baseActionController;
use Laminas\I18n\Validator\IsFloat;
use Laminas\InputFilter\Input;
use Laminas\Json\Json;
use Laminas\Validator\Callback;
use Laminas\Validator\Digits;
use laminas\Validator\StringLength;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use DV\MicroService\TraitModel;
use DV\MicroService\TraitQuery ;

class ActionController extends baseActionController
{
    use TraitModel , TraitQuery ;
	/**
	 * @var AclQuery
	 */
	private $_acl;

    protected $view ;

	public function __construct($options=[])
    {
        if(null == $this->container)    {
            $this->container = ServiceLocatorFactory::getInstance();
        }

        if($this->container->has('twig'))    {
            ##
            $this->view = $this->container->get('twig') ;
        }

	}

	public function getUserInfo()
    {
        $this->getUser() ;
    }
	
	/**
	 * @param AclQuery $acl
	 * @return \DV\Mvc\Controller\ActionController
	 */
	public function setAcl($acl) 
	{
		$this->_acl = $acl;
		return $this;
	}
	
	/**
	 * @param string $resource
	 * @param string $priv
	 * @return boolean
	 */
	protected function isAclAllowed($resource = null, $priv = null)
    {
		return $this->acl->isAllowed($resource, $priv);
	}


	protected function validate(array $options=[] , $returnBool = false)
    {
        $request = $this->getRequest();
        $errorCounter = 0 ;
        $params = $this->getParameters() ;

        ### key is the urlparams to be validated
        foreach($options as $url_key => $value)   {
            ###
            $input = new Input($url_key);
            $input->setValue($params->{$url_key});
           ### break apart the value using | to determine the validators
            $validator_to_consider = explode("|" , $value) ;
            ###

            foreach($validator_to_consider as $validator)   {
                ###
                if($validator == 'required')    {
                    ###
                    $input->setRequired(true) ;
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
                    $input->getValidatorChain()->attach($stringlength);
                }

                if($validator == 'digits')    {
                    $digit = new Digits();
                    $input->getValidatorChain()->attach($digit);
                }

                if($validator == 'float')    {
                    $float = new IsFloat();
                    $input->getValidatorChain()->attach($float);
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
                    $input->getValidatorChain()->attach($array_validator);
                }

            }
            ### validate the current iterated input validator
            if(! $input->isValid($params->toArray()))    {
                ###
                $err_msg = $input->getMessages();
                ### iterate the message
                foreach($err_msg as $msg)   {
                    $this->addFlashMessage(['error' => $msg]) ;
                }

                if($this->isAjax())    {
                    echo Json::encode($msg) ; exit;
                }
                elseif($returnBool)    {
                    return false;
                }else{
                    return back() ;
                }
            }
        }
        return true ;
	}
	
	/**
	 * Trim variables and array (incl. multi-dimensional ones)
	 *
	 * @param mixed $value
	 * @return mixed; null if the param didn't exist
	 */
	private static function trimParam($value) {
		### allow trimparams to runs on each element in the value
		if (is_array($value)) {
			$value =  array_map(array('self', __FUNCTION__), $value);
		}
		
		#$callback = function ($value)	{ } 
		
		$value = trim($value , null , null) ;
		
		return $value ;
	}


	/**
     * Determines if the browser provided a valid SSL client certificate
     *
     * @return boolean True if the client cert is there and is valid
     */
    public function hasValidCert()
    {
        if (!isset($_SERVER['SSL_CLIENT_M_SERIAL'])
            || !isset($_SERVER['SSL_CLIENT_V_END'])
            || !isset($_SERVER['SSL_CLIENT_VERIFY'])
            || $_SERVER['SSL_CLIENT_VERIFY'] !== 'SUCCESS'
            || !isset($_SERVER['SSL_CLIENT_I_DN'])
        ) {
            return false;
        }

        if ($_SERVER['SSL_CLIENT_V_REMAIN'] <= 0) {
            return false;
        }

        return true;
    }

	public function activeTab(array $tabs=['dashboard'])
    {
        $viewModel = $this->view ;
        $viewModel->activeTab = $tabs ;
        return $viewModel ;
    }

    public function getParameters()
    {
        if (! $this->container->has('request_stack')) {
            throw new ServiceNotFoundException('request_stack', null, null, [], 'Request Stack object is required');
        }
        ##
        $requestStack = $this->container->get('request_stack');
        ##
        if(! $requestStack instanceof RequestStack)    {
            ##
            throw new \RuntimeException('Invalid Request Stack parameter') ;
        }
        ##
        $currentRequest = ($requestStack->getCurrentRequest());
        ##
        #if($currentRequest instanceof \Symfony\Component\HttpFoundation\Request )    {}
        ##
        $params = [] ;
        ##
        $query = $currentRequest->query ;
        $server = $currentRequest->server ;
        $request = $currentRequest->request ;
        #if($query instanceof \Symfony\Component\HttpFoundation\ParameterBag )    {}
        $params = array_merge($params , $query->all() , $server->all() , $request->all()) ;
        ##
        return $params ;
    }

    public function getLocator($name)
    {
        return $this->container->get($name) ;
    }
}