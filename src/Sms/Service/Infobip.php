<?php
namespace DV\Mail\Service;


use DV\Mail\Message\SparkPost as messageObject;
use DV\Service\ActionControl;
use SlmMail\Service\AbstractMailService;
use Zend\Config\Config;
use Zend\Http\Response;
use Zend\Mail\Message;
use DV\Mail\RuntimeException ;
use SparkPost\SparkPost ;
use Http\Adapter\Zend\Client as zendClient;

class SparkPostService extends AbstractMailService
{
    /**
     * API endpoint
     */
    const API_ENDPOINT = 'http://api.infobip.com/sms/1/text/single';

    /**
     * Mailgun domain to use
     *
     * @var string
     */
    protected $domain;

    /**
     * Mailgun API key
     *
     * @var string
     */
    protected $apiKey;

    protected $payload ;
    protected $payloadSchema = [

    ] ;

    /**
     * @param string $domain
     * @param string $apiKey
     */
    public function __construct($domain, $apiKey)
    {
        $this->domain = (string)$domain;
        $this->apiKey = (string)$apiKey;
        ##
        $this->setPayload(new Config($this->payloadSchema , true)) ;
    }

    public function setPayload($payload)
    {
        $this->payload = $payload ;
    }
    public function getPayload()
    {
        return $this->payload ;
    }

    /**
     * ------------------------------------------------------------------------------------------
     * MESSAGES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * {@inheritDoc}
     * @link http://documentation.mailgun.com/api-sending.html
     * @return string id of message (if sent correctly)
     */
    public function send(Message $message)
    {
        $from = $message->getFrom();
        if (count($from) !== 1) {
            throw new RuntimeException(
                'Postage API requires exactly one from sender'
            );
        }
        try {
            ##
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "http://api.infobip.com/sms/1/text/single",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{ \"from\":\"InfoSMS\", \"to\":\"41793026727\", \"text\":\"Test SMS.\" }",
                CURLOPT_HTTPHEADER => array(
                    "accept: application/json",
                    "authorization: Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==",
                    "content-type: application/json"
                ),
            ));
        }
        catch (\Exception $e) {
            ##
            throw new RuntimeException($e->getMessage() , 500 , $e) ;
        }
    }

    /**
     * @param string $uri
     * @param array $parameters
     * @param bool $perDomain
     * @return zendClient
     */
    private function prepareHttpClient()
    {
        $httpClient = new \Http\Adapter\Guzzle6\Client(new \GuzzleHttp\Client());
        return $httpClient ;
    }


    public function prepareReceiver(Message $message)
    {

        $payload = $this->getPayload() ;

        if(ActionControl::ZERO < count($message->getTo())) {
            $to = [];
            foreach ($message->getTo() as $address) {
                $to[] = ['address' => ['name' => $address->getName(), 'email' => $address->getEmail(), 'name']];
            }
            $payload->offsetSet('recipients', $to);
        }else{
            $payload->offsetUnset('recipients') ;
        }

        if(ActionControl::ZERO < count($message->getCc()))    {
            $cc = [];
            foreach ($message->getCc() as $address) {
                $cc[] = ['address' => ['name' => $address->getName() , 'email' => $address->getEmail()]];
            }
            $payload->offsetSet('cc' , $cc) ;
        }
        else{
            $payload->offsetUnset('cc') ;
        }

        if(ActionControl::ZERO < count($message->getBcc())) {
            $bcc = [];
            foreach ($message->getBcc() as $address) {
                $bcc[] = ['address' => ['name' => $address->getName(), 'email' => $address->getEmail()]];
            }
            $payload->offsetSet('bcc', $bcc);
        }
        else{
            $payload->offsetUnset('bcc') ;
        }

        ##
        $this->setPayload($payload) ;
    }

    public function prepareMessageContent(Message $message)
    {
        $payload = $this->getPayload() ;
        $content = $payload->get('content') ;

        $from = $message->getFrom()->current() ;
        ##
        $messageFrom = ['name' => $from->getName() , 'email' => $from->getEmail()] ;

        $content->offsetSet('from' , $messageFrom) ;

        $content->offsetSet('subject' , $message->getSubject()) ;

        /*if(null != $message->getHeaders())    {
            $content->offsetSet('headers' , (object) $message->getHeaders()->toArray()) ;
        }
        else{
        }*/
        $content->offsetSet('headers' , new \stdClass()) ;
        ##
        $content->offsetSet('text' , $this->extractText($message)) ;
        $content->offsetSet('html' , $this->extractHtml($message)) ;
        ##
        $this->setPayload($payload) ;
    }
}
