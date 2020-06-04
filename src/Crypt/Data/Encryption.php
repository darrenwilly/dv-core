<?php
namespace DV\Crypt\Data ;

use Laminas\Crypt\Key\Derivation\Pbkdf2 ;
use DV\Crypt\CryptAbstract ;
use Laminas\Crypt\BlockCipher;
use Laminas\Crypt\PublicKey\RsaOptions;
use Laminas\Crypt\PublicKey\Rsa;

class Encryption
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
	
	const PRIVATE_CERT_FILE = 'private_key.pem' ;
	
	const PUBLIC_CERT_FILE = 'public_key.pub' ;
	
	const KEY_FILE = 'pass_phrase.key' ;
	
	const CLIENT_CERT_DIR = '/../data/encryption/client' ; 
	
	const CLOUD_CERT_DIR = '/../data/encryption/cloud' ;


	/**
	 * Generate a strong encryption string from a passPhrase
	 *
	 * @param $_options
	 * @return string
	 * @throws \Exception
	 * @internal param array|string $phrase
	 */
	public function pass_phrase($_options)
	{	
		$binary = false ;
		
		if(is_array($_options))	{
			if(! isset($_options['phrase']))	{
				return false ;
			}
			
			$phrase = $_options['phrase'] ;
			
			if(! isset($_options['iteration']))	{
				$iteration =  self::DEFAULT_PHRASE_ITERATION ;
			}
			else{
				$iteration = $_options['iteration'] ;
			}
			
			if(isset($_options['binary']))	{
				$binary = $_options['binary'] ;
			}
		}	
		else{
			
			$iteration =  self::DEFAULT_PHRASE_ITERATION ;
			
			if(is_string($_options))	{
				$phrase = $_options ;
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
		$hex_pass_phrase =  bin2hex($key) ;
		
		if(isset($_options['file']))	{
			
			if(is_dir($_options['file']))	{
				$key_file = $_options['file'].'/'.self::KEY_FILE ;
			}
			else{
				$key_file = self::CLIENT_CERT_DIR.'/'.self::KEY_FILE ;
			}
			
			file_put_contents(realpath($key_file) , $hex_pass_phrase) ;
		}
		
		return $hex_pass_phrase ;
	}
	
	/**
	 * initiate the block cipher engine
	 * @param array $_options
	 * @return \Laminas\Crypt\BlockCipher
	 */
	public function block_cipher(array $_options)
	{
		$blockCipher = BlockCipher::factory('mcrypt', [
				'algo' => 'aes',
				'mode' => 'cbc',
				'hash' => 'sha256'
		]);
		
		if(isset($_options['pass_phrase_key']))	{
			###
			$blockCipher->setKey($_options['pass_phrase_key']) ;
		}		
		
		return $blockCipher ;
	}
	
	/**
	 * 
	 * @param array $options
	 * @throws \Exception
	 */ 
	public function block_cipher_operation(array $options)
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
		if(! isset($options['pass_phrase_key']))	{
			throw new \Exception('pass phrase key to use for algorithm operation must be set') ;
		} 
		
		return $this->block_cipher($options)->{$todo}($data) ;
	}
	
	/**
	 * Generate a public / private RSA Certificate file .
	 * 
	 * @param array $_options
	 * @throws \Exception
	 * @return \Laminas\Crypt\PublicKey\RsaOptions
	 */
	public function rsa_generate_cert($_options)
	{	
		$rsaOptions = new RsaOptions();
		
		### set the data to encrypt/decrypt
		if(! isset($_options['pass_phrase_key']))	{
			throw new \Exception('pass phrase key to use for algorithm operation must be set') ;
		}
		### pass and set the phrase
		$rsaOptions->setPassPhrase($_options['pass_phrase_key']) ;
		
		### set the data to encrypt/decrypt
		if(! isset($_options['rsa_key_options']))	{
			/**
			 * config key must be set inorder to openSSL to work on PHP Engine
			 */
			$rsa_key_options['rsa_key_options'] = [ 'config' => 'C:\Laminas\Apache2\conf\openssl.cnf',
													'private_key_bits' => 2048,
									                'private_key_type' => OPENSSL_KEYTYPE_RSA, 
									                'digest_alg'       => 'sha512',													
									              ] ;		
		}
	
		## set options needed to generate key
		$rsaOptions->generateKeys($rsa_key_options['rsa_key_options']);
		
		### set the data to encrypt/decrypt
		if(isset($_options['dir']))	{
			### check for private directory
			if(isset($_options['dir']['private']))	{
				$private_dir = $_options['dir']['private'];
			}
			else{
				$private_dir = APPLICATION_PATH . self::CLIENT_CERT_DIR ;
			}
			
			### check for public directory
			if(isset($_options['dir']['public']))	{
				$public_dir = $_options['dir']['public'] ;
			}
			else{
				$public_dir = APPLICATION_PATH . self::CLIENT_CERT_DIR ;	
			}
			
			### write private certification informaton to file
			file_put_contents(realpath($private_dir.'/'.self::PRIVATE_CERT_FILE) , $rsaOptions->getPrivateKey());
			### write public certificate information to file
			file_put_contents(realpath($public_dir.'/'.self::PUBLIC_CERT_FILE) , $rsaOptions->getPublicKey());
		}
		
		return $rsaOptions ;
	}
	
	/**
	 * Encrypt / Decrypt a data that has already be encrypted using BlockCipher (bcos of RSA 1960bit limitation)
	 * 
	 * @param array $_options
	 * @throws \Exception
	 * @return \Laminas\Crypt\PublicKey\Rsa
	 */
	public function rsa_operation($_options)
	{
		### set the data to encrypt/decrypt
		if(! isset($_options['data']))	{		 
			throw new \Exception('data must be set') ;
		}
		
		### check for action to apply from either encrypt/decrypt
		if(! isset($_options['todo']))	{
			$_options['todo']  = 'encrypt' ;
		} 
		
		$rsa_config = ['binary_output' => false] ;

		$data = $_options['data'] ;
		
		if('encrypt' == $_options['todo'])	{	
			### set the data to encrypt/decrypt
			if(! isset($_options['public_key']))	{
				throw new \Exception('public key information / directory must be provided') ;
			}
					
			$rsa_config['public_key'] = $_options['public_key'] ;
		}
		
		if('decrypt' == $_options['todo'])	{			
			if(! isset($_options['private_key']))	{
				throw new \Exception('private key information / directory must be provided') ;
			}
			
			if(! isset($_options['pass_phrase']))	{
				throw new \Exception('pass phrase information / directory must be provided') ;
			}
			
			$rsa_config['private_key'] = $_options['private_key'] ;
			$rsa_config['pass_phrase'] = $_options['pass_phrase'] ;
		}
		
		$todo = (string) $_options['todo'] ;
		
		$rsa = Rsa::factory($rsa_config);
		
		return $rsa->{$todo}($data) ;
	}
	
	
	/** 
	 * Encrypt string using openSSL module 
	 * @param string $textToEncrypt 
	 * @param string $encryptionMethod One of built-in 50 encryption algorithms 
	 * @param string $secretHash Any random secure SALT string for your website 
	 * @param bool $raw If TRUE return base64 encoded string 
	 * @param string $password User's optional password 
	 */ 
	public static function encryptOpenssl($textToEncrypt, $encryptionMethod = 'AES-256-CFB', $secretHash = self::SALT, $raw = false, $password = '')
	{		 
		$length = openssl_cipher_iv_length($encryptionMethod);
		 
		$iv = substr(md5($password), 0, $length);
		 
		return openssl_encrypt($textToEncrypt, $encryptionMethod, $secretHash, $raw, $iv);		 
	}
	
	
	/** 
	* Decrypt string using openSSL module 
	* @param string $textToDecrypt 
	* @param string $encryptionMethod One of built-in 50 encryption algorithms 
	* @param string $secretHash Any random secure SALT string for your website 
	* @param bool $raw If TRUE return base64 encoded string 
	* @param string $password User's optional password 
	*/ 
	public static function decryptOpenssl($textToDecrypt, $encryptionMethod = 'AES-256-CFB', $secretHash = self::SALT, $raw = false, $password = '')
	{
		$length = openssl_cipher_iv_length($encryptionMethod);
		$iv = substr(md5($password), 0, $length);
		
		return openssl_decrypt($textToDecrypt, $encryptionMethod, $secretHash, $raw, $iv);
		
	}
	
}