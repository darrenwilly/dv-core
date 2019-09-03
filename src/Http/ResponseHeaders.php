<?php
namespace DV\Http;

use Carbon\Carbon;
use DV\Mvc\Service\ServiceLocatorFactory;
use DV\Http\AppLogicKeys as requiredXHeaders;
use Zend\Http\Response;
use Zend\Json\Json;
use Zend\Mvc\MvcEvent;

trait ResponseHeaders
{
    use requiredXHeaders  ;

    public function jsonErrorHeaders(Response &$response , $responseHeader=null)
    {
        $self_string = get_class() ;

        if(0 === strpos(Response::class , $self_string))    {
            $response = $this ;
        }

        if(null == $response)    {
            $response = ServiceLocatorFactory::getResponse() ;
        }

        if(null == $response->getStatusCode())    {
            ## start preparing error response
            $response->setStatusCode(Response::STATUS_CODE_401);
        }

        if(null == $responseHeader)    {
            $responseHeader = $response->getHeaders() ;
        }

        if(! $responseHeader->has('Access-Control-Allow-Origin')) {
            $responseHeader->addHeaderLine('Access-Control-Allow-Origin', '*');
        }

        if(! $responseHeader->has('Access-Control-Allow-Headers')) {
            $responseHeader->addHeaderLine('Access-Control-Allow-Headers', 'Content-Type,Accept,' . self::$X_PLATFORM . ',' . self::$AUTHORIZATION . ',' . self::$AUTHENTICATION);
        }

        if(! $responseHeader->has('Content-Type')) {
            $responseHeader->addHeaderLine('Content-Type', 'application/problem+json');
        }

        if(! $responseHeader->has('Accept')) {
            $responseHeader->addHeaderLine('Accept', 'application/problem+json');
        }
    }

    public function jsonAuthErrorHeaders(Response &$response)
    {
        $this->jsonErrorHeaders($response) ;
        ## fetch header
        $responseHeader = $response->getHeaders() ;
        ### add extra Authentication Error Header
        $responseHeader->addHeaderLine(self::$X_AUTH_ERROR , 'failed authentication where required');
    }

    public function jsonAclErrorHeaders(Response &$response )
    {
        $this->jsonErrorHeaders($response) ;
        ## fetch header
        $responseHeader = $response->getHeaders() ;
        ### add extra Authentication Error Header
        $responseHeader->addHeaderLine(self::$X_ACL_ERROR , 'failed authorization to access resources');
    }

    public function jsonResponseHeader(Response &$response , $responseHeader=null)
    {
        if($this instanceof Response)    {
            $response = $this ;
        }
        if(null == $response)    {
            $response = ServiceLocatorFactory::getResponse() ;
        }

        if(null == $response->getStatusCode())    {
            ## start preparing error response
            $response->setStatusCode(Response::STATUS_CODE_200);
        }

        if(null == $responseHeader)    {
            $responseHeader = $response->getHeaders() ;
        }

        $this->alwayExpectedHeader($responseHeader) ;

        if(! $responseHeader->has('Access-Control-Allow-Origin')) {
            $responseHeader->addHeaderLine('Access-Control-Allow-Origin', '*');
        }

        if(! $responseHeader->has('Access-Control-Allow-Credentials')) {
            $responseHeader->addHeaderLine('Access-Control-Allow-Credentials', true);
        }
        #$responseHeader->addHeaderLine('Access-Control-Max-Age', '1728000');
        if(! $responseHeader->has('Access-Control-Allow-Methods')) {
            $responseHeader->addHeaderLine('Access-Control-Allow-Methods', 'GET,HEAD,POST,DEBUG,PUT,DELETE,PATCH,OPTIONS');
        }

        if(! $responseHeader->has('Access-Control-Allow-Headers')) {
            $responseHeader->addHeaderLine('Access-Control-Allow-Headers', 'Keep-Alive,User-Agent,X-Requested-With,X-Requested-By,Cache-Control,Content-Type,Content-Range,Range,Accept,' . self::$X_PLATFORM . ',' . self::$AUTHORIZATION . ',' . self::$AUTHENTICATION);
        }

        if(! $responseHeader->has('Content-Type')) {
            $responseHeader->addHeaderLine('Content-Type', 'application/json; charset=utf8');
        }

        if(! $responseHeader->has('Accept')) {
            $responseHeader->addHeaderLine('Accept', 'application/json');
        }
    }

    public function alwayExpectedHeader(&$responseHeader)
    {
        ### set the global header for any time of response
        $responseHeader->addHeaderLine("Cache-Control" , "no-cache, must-revalidate");
        $responseHeader->addHeaderLine("Expires" , Carbon::instance(new \DateTime('last year'))->toRfc850String());
    }

    public function xServerRsaId(&$responseHeader)
    {
        $rsa_dir = APPLICATION_DATA_PATH .'/rsa/certificate/server' ;
        ###
        if(! dir($rsa_dir))    {
            throw new \Exception('Invalid RSA Certificate Folder Manager') ;
        }
        ###
        $cert_file  = $rsa_dir.DIRECTORY_SEPARATOR.'public.pub' ;
        if(! file_exists($cert_file))    {
            throw new \Exception('RSA certificate file not found') ;
        }

        ### fetch the certificate
        $rsa_server_id = file_get_contents($cert_file) ;
        ### make sure the file certifcate exist
        if(null == strlen($rsa_dir))    {
            throw new \Exception('Corrupted RSA Certificate file found') ;
        }

        ## set the global header for any time of response
        #$responseHeader->addHeaderLine(self::$X_RSA_SERVER_ID , base64_encode($rsa_server_id));
    }

    public function xAuthUser(MvcEvent &$event)
    {
        ### fetch the Request User RSA ID
        #$x_rsa_id = $event->getParam(self::$X_RSA_ID) ;
        ### fetch the Token Authenticated User
        $user_token_data = $event->getParam(self::$X_AUTHENTICATED_USER) ;

        $user_data_to_encrypt = Json::encode([$user_token_data]) ;

        /*$rsa_enginner = new Engineer() ;
        $encryptedToken = $rsa_enginner->rsaOperation(['todo' => 'encrypt' , 'public_key' => $x_rsa_id , 'data' => $user_data_to_encrypt]) ;*/

        ### for now bcos our focus is for web, then we can manage base64_encode for login credentials in header
        $encryptedToken = base64_encode($user_data_to_encrypt) ;

        $response = $event->getResponse() ;
        $responseHeader = $response->getHeaders() ;
        ### set the global header for any time of response
        $responseHeader->addHeaderLine(self::$X_AUTH_USER , $encryptedToken);
        ## add the server public rsa key
        $this->xServerRsaId($responseHeader) ;
        ## add the general jsonResponseHeader
        $this->jsonResponseHeader($response) ;
    }
}