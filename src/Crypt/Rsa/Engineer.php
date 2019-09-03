<?php
namespace DV\Crypt\Rsa ;

use Zend\Crypt\Key\Derivation\Pbkdf2 ;
use DV\Crypt\CryptAbstract ;
use Zend\Crypt\BlockCipher;
use Zend\Crypt\PublicKey\RsaOptions;
use Zend\Crypt\PublicKey\Rsa;

class Engineer
{
    use CryptAbstract ;
    
	/**
	 * the default iteration value for passphrase generation which will depend on the the CPU Power
	 * e.g Corei5 Process should be like 500,000, Corei7 - 1,000,000
	 * @var int
	 */
	const DEFAULT_PHRASE_ITERATION = 100000 ;
	
	const PUBLIC_NAME = 'public' ;
	
	const PRIVATE_NAME = 'private' ; 
	
	const PRIVATE_CERT_FILE = 'private.pem' ;
	
	const PUBLIC_CERT_FILE = 'public.pub' ;
	
	const KEY_FILE = 'passphrase.key' ;
	
	const CLIENT_CERT_DIR = 'rsa/certificate/client' ; 
	
	const CLOUD_CERT_DIR = 'rsa/certificate/server' ;


	/**
	 * Generate a strong encryption string from a passPhrase
	 *
	 * @param $options
	 * @return string
	 * @throws \Exception
	 * @internal param array|string $phrase
	 */
	public function passPhrase($options)
	{	
		$binary = false ;
		
		if(is_array($options))	{
			if(! isset($options['phrase']))	{
				return false ;
			}
			$phrase = $options['phrase'] ;
			
			if(! isset($options['iteration']))	{
				$iteration =  self::DEFAULT_PHRASE_ITERATION ;
			}
			else{
				$iteration = $options['iteration'] ;
			}
			
			if(isset($options['binary']))	{
				$binary = $options['binary'] ;
			}
		}	
		else{
			
			$iteration =  self::DEFAULT_PHRASE_ITERATION ;
			
			if(is_string($options))	{
				$phrase = $options ;
			}
		}
		
		if(null == $phrase)	{
			throw new \Exception('phrase require a value to generate passPhrase') ;
		}
		
		$salt = CryptAbstract::getSalt() ; 
		### fetch a mathematical calculation of phrase word
		$key = Pbkdf2::calc('sha512', $phrase, $salt, $iteration, strlen($phrase)*2) ;
		
		### check if binary should be the output
		if($binary)	{
			return $key ;
		}

		### return hexadecimal valut;
		$hex_passPhrase =  bin2hex($key) ;

		if(isset($options['file']))	{

			if(is_dir($options['file']))	{
				$key_file = realpath($options['file']).'/'.self::KEY_FILE ;
			}
			else{
				$key_file = self::CLIENT_CERT_DIR.'/'.self::KEY_FILE ;
			}
            ###
			file_put_contents($key_file , $hex_passPhrase) ;
		}
		
		return $hex_passPhrase ;
	}
	
	/**
	 * initiate the block cipher engine
	 * @param array $options
	 * @return \Zend\Crypt\BlockCipher
	 */
	public function blockCipher(array $options)
	{
		$blockCipher = BlockCipher::factory('mcrypt', [
				'algo' => 'aes',
				'mode' => 'cbc',
				'hash' => 'sha256'
		]);
		
		if(isset($options['passPhraseKey']))	{
			###
			$blockCipher->setKey($options['passPhraseKey']) ;
		}		
		
		return $blockCipher ;
	}
	
	/**
	 * 
	 * @param array $options
	 * @throws \Exception
	 */ 
	public function blockCipherOperation(array $options)
	{
		### set the data to encrypt/decrypt
		if(isset($options['data']))	{
			$data = $options['data'] ;
			unset($options['data']) ;
		}
		else{
			throw new \Exception('data must be set') ;
		}
		
		### check for action to apply from either encrypt/decrypt
		if(isset($options['todo']))	{
			$todo = $options['todo'] ;
			unset($options['todo']) ;
		}
		else{
			$todo = 'encrypt' ;
		}

		### set the data to encrypt/decrypt
		if(! isset($options['passPhraseKey']))	{
			throw new \Exception('pass phrase key to use for algorithm operation must be set') ;
		} 
		
		return $this->blockCipher($options)->{$todo}($data) ;
	}
	
	/**
	 * Generate a public / private RSA Certificate file .
	 * 
	 * @param array $options
	 * @throws \Exception
	 * @return \Zend\Crypt\PublicKey\RsaOptions
	 */
	public function rsaGenerateCert($options)
	{	
		$rsaOptions = new RsaOptions();
		
		### set the data to encrypt/decrypt
		if(! isset($options['passPhraseKey']))	{
			throw new \Exception('pass phrase key to use for algorithm operation must be set') ;
		}
		### pass and set the phrase
		$rsaOptions->setPassPhrase($options['passPhraseKey']) ;
		
		### set the data to encrypt/decrypt
		if(! isset($options['rsa_key_options']))	{
			/**
			 * config key must be set inorder to openSSL to work on PHP Engine
			 */
            $options['rsa_key_options'] = [ 'config' => OPENSSL_CONF_FILE ,
													'private_key_bits' => 2048,
									                'private_key_type' => OPENSSL_KEYTYPE_RSA, 
									                'digest_alg'       => 'sha512',													
			] ;
		}

		## set options needed to generate key
		$rsaOptions->generateKeys($options['rsa_key_options']);
		
		### set the data to encrypt/decrypt
		if(isset($options['dir']))	{
            ###
			$master_cert_dir = $options['dir'] ;
			### write private certification informaton to file
			file_put_contents($master_cert_dir.'/'.self::PRIVATE_CERT_FILE , $rsaOptions->getPrivateKey());
			### write public certificate information to file
			file_put_contents($master_cert_dir.'/'.self::PUBLIC_CERT_FILE , $rsaOptions->getPublicKey());
		}
		
		return $rsaOptions ;
	}
	
	/**
	 * Encrypt / Decrypt a data that has already be encrypted using BlockCipher (bcos of RSA 1960bit limitation)
	 * 
	 * @param array $options
	 * @throws \Exception
	 * @return \Zend\Crypt\PublicKey\Rsa
	 */
	public function rsaOperation($options)
	{
		### set the data to encrypt/decrypt
		if(! isset($options['data']))	{		 
			throw new \Exception('data must be set') ;
		}
		
		### check for action to apply from either encrypt/decrypt
		if(! isset($options['todo']))	{
			$options['todo']  = 'encrypt' ;
		} 
		
		$rsa_config = ['binary_output' => false] ;

		$data = $options['data'] ;
		
		if('encrypt' == $options['todo'])	{	
			### set the data to encrypt/decrypt
			if(! isset($options['public_key']))	{
				throw new \Exception('public key information / directory must be provided') ;
			}
					
			$rsa_config['public_key'] = $options['public_key'] ;
		}
		
		if('decrypt' == $options['todo'])	{			
			if(! isset($options['private_key']))	{
				throw new \Exception('private key information / directory must be provided') ;
			}
			
			if(! isset($options['passPhrase']))	{
				throw new \Exception('pass phrase information / directory must be provided') ;
			}
			
			$rsa_config['private_key'] = $options['private_key'] ;
			$rsa_config['passPhrase'] = $options['passPhrase'] ;
		}
		
		$todo = (string) $options['todo'] ;
		
		$rsa = Rsa::factory($rsa_config);
		
		return $rsa->{$todo}($data) ;
	}
	
	
	/** 
	 * Encrypt string using openSSL module using PHP Native
	 * @param string $textToEncrypt 
	 * @param string $encryptionMethod One of built-in 50 encryption algorithms 
	 * @param string $secretHash Any random secure SALT string for your website 
	 * @param bool $raw If TRUE return base64 encoded string 
	 * @param string $password User's optional password 
	 */ 
	public static function encryptOpenssl($textToEncrypt, $encryptionMethod = 'AES-256-CFB',  $raw = false, $password = '')
	{		 
		$length = openssl_cipher_iv_length($encryptionMethod);
		 
		$iv = substr(md5($password), 0, $length);
		 
		return openssl_encrypt($textToEncrypt, $encryptionMethod, $password, $raw, $iv);
	}
	
	
	/** 
	* Decrypt string using openSSL module using PHP Native
	* @param string $textToDecrypt 
	* @param string $encryptionMethod One of built-in 50 encryption algorithms 
	* @param string $secretHash Any random secure SALT string for your website 
	* @param bool $raw If TRUE return base64 encoded string 
	* @param string $password User's optional password 
	*/ 
	public static function decryptOpenssl($textToDecrypt, $encryptionMethod = 'AES-256-CFB', $raw = false, $password = '')
	{
		$length = openssl_cipher_iv_length($encryptionMethod);
		$iv = substr(md5($password), 0, $length);
		
		return openssl_decrypt($textToDecrypt, $encryptionMethod, $password, $raw, $iv);
	}
	
}