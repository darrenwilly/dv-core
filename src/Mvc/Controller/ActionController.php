<?php
declare(strict_types=1);

namespace DV\Mvc\Controller ;

use DV\ContainerService\ServiceLocatorFactory;
use Shared\Core\Security\Acl\Veiw;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as baseActionController;
use Laminas\I18n\Validator\IsFloat;
use Laminas\InputFilter\Input;
use Laminas\Json\Json;
use Laminas\Validator\Callback;
use Laminas\Validator\Digits;
use laminas\Validator\StringLength;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use DV\MicroService\TraitModel;
use DV\MicroService\TraitQuery ;
use Shared\Core\Security\Auth\Authentication;
use Veiw\Core\Service\FlashMessenger;
use Veiw\Core\Service\Vurl;
use Veiw\Infrastructure\View\ViewModel;

class ActionController extends baseActionController
{
    use TraitModel , TraitQuery ;
	/**
	 * @var AclQuery
	 */
	private $acl;

    protected $view ;

	public function __construct($options=[])
    {
        if(null == $this->container)    {
            $this->container = ServiceLocatorFactory::getInstance();
        }
	}

	/**
	 * @param AclQuery $acl
	 * @return bool
	 */
	public function acl($resourceOrRoleAsAttribute , $privilegeOrResourceOrRoleAsSubject=false)
	{
		return $this->acl->isAllowed($resourceOrRoleAsAttribute , $privilegeOrResourceOrRoleAsSubject);
	}
	
	/**
	 * @param string $resource
	 * @param string $priv
	 * @return boolean
	 */
	protected function isAclAllowed($resource = null, $priv = null)
    {
		return $this->acl($resource, $priv);
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

	public function activeTab(array $tabs=['dashboard'] , $existingModel=null)
    {
        if(null == $existingModel)    {
            $viewModel = new ViewModel() ;
        }else{
            $viewModel = $existingModel ;
        }
        $viewModel->setVariable('activeTab'  , $tabs) ;
        return $viewModel ;
    }

    public function getParameters(array $defaults = array() , $returnLaminasParameter=false , $optionsToReturn=[])
    {
        ##
        return ServiceLocatorFactory::getParameters($defaults , $optionsToReturn , $returnLaminasParameter) ;
    }

    /**
     * @param $name
     * @return object|callable|null
     * @throws \Exception
     */
    public function getLocator($name)
    {
        try{
            ##
            $service = $this->container->get($name) ;
        }
        catch (\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException $exception )   {
            ##
            $service = ServiceLocatorFactory::getLocator($name) ;
        }
        return $service ;
    }

    /**
     * Proxy to Verify Authentications
     * @param array $options
     * @return mixed
     */
    public function getUserInfo($options=[])
    {
        ##
        if( (! method_exists($this , 'getContainer')) && (! property_exists( $this ,'container')) )    {
            ##
            throw new \RuntimeException('Container Service object is required to perform the next logic') ;
        }
        try{
            ##
            $security_checker = $this->getLocator(Authentication::class) ;

        }
        catch (\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException $exception )   {
            ##
            $security_checker = ServiceLocatorFactory::getLocator(Authentication::class) ;
        }
        catch (\Throwable $exception)   {
            ##
            $security_checker = ServiceLocatorFactory::getLocator(Authentication::class) ;
        }
        ##
        return $security_checker->getUserInfo($options) ;
    }

    public function addFlashMessage($message)
    {
        ##
        $messanger = $this->getLocator(FlashMessenger::class) ;
        ##
        if(is_numeric($message))    {
            return $messanger->message($message) ;
        }
        ##
        return $messanger->addFlashMessage($message) ;
    }

    public function _goto($options)
    {
        if(is_string($options))    {
            $options = ['route' => $options , 'params' => [] ] ;
        }

        if(! isset($options['route']))    {
            ##
            if(isset($options['label']))    {
                ##
                $options['route'] = $options['label'] ;
                unset($options['label']) ;
            }else{
                $options['route'] = 'general-default' ;
            }
            ##
            $options['params'] = $options ;
        }
        /**
         * when an error occured might mean the router does not exist, then use vurl to generate the link
         */
        try{
            ##
            return $this->redirectToRoute($options['route'] , $options['params']) ;
        }
        catch (\Throwable $exception)   {
            ##
            $vurl = $this->getLocator(Vurl::class) ;
            ##
            if(isset($options['route']))    {
                ## change the key to link
                $options['link'] = $options['route'] ;
                ## unset route
                unset($options['route']);
            }
            ##
            return $this->redirect($vurl($options)) ;
        }
    }

    /**
     * Proxy for actions in Controller that has no route defined
     * @param $name
     * @param $arguments

    public function __call($name, $arguments)
    {
        ##
        $current_object = get_class($this) ;
        ## when the method exist in the controller, redirect to it
        if(method_exists($current_object , $name))    {
            ##
            return call_user_func_array([$current_object , $name] , $arguments) ;
        }
        ## when that method don't exist
        throw new \RuntimeException('Invalid Controller Actions called, Please check the route configuration') ;
    }*/
}