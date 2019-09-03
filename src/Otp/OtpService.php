<?php
namespace DV\Otp;

use Carbon\Carbon;
use DV\Service\ActionControl;
use OTPHP\TOTP;
use DV\Doctrine\Doctrine;
use DV\Model\Lookup;
use Zend\Config\Config;


class OtpService
{
    use Doctrine ;

    const KEY_OTP = 'otp';
    const KEY_OTP_SETTINGS = 'otpSettings';
    /**
     * default OTP Lifetime
     */
    const LIFETIME = 30 ;
    /**
     * default OTP Digest
     */
    const DIGEST = 'sha512';
    /**
     * default OTP Length
     */
    const LENGTH = 7 ;

    const TOTP = 'totp' ;
    const HOTP = 'hotp' ;


    public static $OTP_SCHEMA_TEMPLATE = [
        self::KEY_OTP => [
                'secret' => null ,
                'label' => null ,
                'issuer' => PROJECT_NAME ,
                'lifetime' => self::LIFETIME , ## (timestamp (totp) / counter(hotp)) should be overwrite if this is not wanted
                'digest' => self::DIGEST , ## should be replaced incase it neeed to change
                'length' => self::LENGTH ,
                'extras' => [],
            self::KEY_OTP_SETTINGS => [
                'type' => self::TOTP
            ]
        ] ,

    ] ;

    /**
     * The time the otp should start counting from as the Generation Time
     * @var int
     */
    protected $generationEpochTime ;
    protected $lifetime ;

    /**
     * Generate an OTP instance from Doctrine Entity
     * @param $repository_entity_row
     * @return TOTP
     */
    public function otpServiceFromEntity($repository_entity_row)
    {
        if(! class_exists($repository_entity_row) )    {
            throw new RuntimeException('Instance of Doctrine Entity is required') ;
        }

        if(! method_exists($repository_entity_row , 'getExtras'))    {
            throw new RuntimeException('Instance of Doctrine Entity provided but the Extras Column cannot be found') ;
        }
        ##
        $extras_entity_row = $repository_entity_row->getExtras('config') ;

        if(! $extras_entity_row instanceof Config)    {
            throw new RuntimeException('instance of Zend\Config\Config required for better logic management') ;
        }

        if(! $extras_entity_row->offsetExists(self::KEY_OTP))    {
            ##
            $repository_entity_row = $this->createOTPMetadataForEntity($repository_entity_row) ;
            ##
            return $this->otpServiceFromEntity($repository_entity_row);
        }
        ##
        return $this->otpService($extras_entity_row) ;
    }

    public function otpService($options)
    {
        ##
        if(is_array($options) )   {
            $extras_entity_row = new Config($options , true) ;
        }

        if(! $options instanceof Config)    {
            throw new RuntimeException('OTP Schema Error: Invalid OTP schema structure') ;
        }
        $extras_entity_row = $options ;

        if(! $extras_entity_row->offsetExists(self::KEY_OTP))    {
            ##
            throw new RuntimeException('instance of Zend\Config\Config required for better logic management') ;
        }
        ##
        $otp_entity_schema = $extras_entity_row->get(self::KEY_OTP) ;
        $otp_settings_entity_schema = $otp_entity_schema->get(self::KEY_OTP_SETTINGS) ;

        $lifetime = (null != $this->getLifetime()) ? $this->getLifetime() : $otp_entity_schema->lifetime ;
        #$lifetime = 30;

        ##
        switch($otp_settings_entity_schema->get('type'))    {
            case self::TOTP :
            default:
                ##
                $totp = \OTPHP\TOTP::create($otp_entity_schema->secret ,  $lifetime , $otp_entity_schema->digest , $otp_entity_schema->length , $this->getEpoch()) ;
                break ;
            ##
            case self::HOTP :
                $totp = \OTPHP\HOTP::create($otp_entity_schema->secret , $lifetime , $otp_entity_schema->digest , $otp_entity_schema->length) ;
                break ;
        }

        $totp->setLabel($otp_entity_schema->label) ;
        $totp->setIssuer($otp_entity_schema->issuer) ;
        ##
        $userOtpExtras = $otp_entity_schema->extras ;
        if(ActionControl::ZERO < count($userOtpExtras))    {
            ##
            foreach ($userOtpExtras as $itemKey => $item)  {
                ##
                $totp->setParameter($itemKey , $item) ;
            }
        }
        #var_dump($totp->getProvisioningUri());exit;
        ##
        return $totp ;
    }


    protected function createOTPMetadataForEntity($repository_entity_row)
    {
        try{
            $repository_entity_column['extras'] = call_user_func(function() use($repository_entity_row) {
                ##
                $extras_entity_row = $repository_entity_row->getExtras('config') ;
                ##
                if($extras_entity_row->offsetExists(self::KEY_OTP))    {
                    ##
                    $extras_entity_row->merge(new Config(self::$OTP_SCHEMA_TEMPLATE, true)) ;
                }
                ##
                $otp_entity_schema = $extras_entity_row->get(self::KEY_OTP) ;

                return $extras_entity_row->toArray() ;
            }) ;
            ##
            $model = new Lookup() ;
            $emWrite = $model->getDoctrineEntityManager(self::createEntityIdentifier(DOCTRINE_ORM_WRITE)) ;
            ##
            $model->setEntityParams($repository_entity_row , $repository_entity_column) ;
            ##
            $emWrite->persist($repository_entity_row) ;
            $emWrite->flush($repository_entity_row) ;
            ##
            return $repository_entity_row ;
        }
        catch (\Exception $exception)  {
            throw new RuntimeException($exception->getMessage() , 500 , $exception) ;
        }
    }


    public function getEpoch()
    {
        return $this->generationEpochTime ;
    }
    public function setEpoch($epoch)
    {
        $this->generationEpochTime = $epoch ;
    }

    public function getLifetime()
    {
        return $this->lifetime ;
    }
    public function setLifetime($lifetime)
    {
        $this->lifetime = $lifetime ;
    }
}