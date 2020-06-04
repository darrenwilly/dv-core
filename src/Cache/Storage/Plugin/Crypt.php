<?php
namespace DV\Cache\Storage\Plugin ;

use Laminas\Cache\Storage\Plugin;
use Laminas\Cache\Storage\Event;
use Laminas\Cache\Storage\PostEvent;
use DV\Crypt\Filter\Decrypt as data_decryptor ;
use DV\Crypt\Filter\Encrypt as data_encryptor ;

class Crypt extends Plugin\Serializer
{
    

    /**
     * On read item post
     *
     * @param  PostEvent $event
     * @return void
     */
    public function onReadItemPost(PostEvent $event)
    {
        $serializer = $this->getOptions()->getSerializer();
        $result     = $event->getResult();
        $params     = $event->getParams();
        
        ### time to decrypt the result
        $decrypt = new data_decryptor() ;
        $decrypt->setDataLockKey(md5($params['key'])) ;
        $result = $decrypt->filter($result) ;
        
        $result     = $serializer->unserialize($result);
        $event->setResult($result);
    }

    /**
     * On read items post
     *
     * @param  PostEvent $event
     * @return void
     */
    public function onReadItemsPost(PostEvent $event)
    {
        $serializer = $this->getOptions()->getSerializer();
        $result     = $event->getResult();
        $params     = $event->getParams();
        
        foreach ($result as &$value) {           
            ### time to decrypt the result
            $decrypt = new data_decryptor() ;
            $decrypt->setDataLockKey(md5($params['key'])) ;
            $decrypted_value = $decrypt->filter($value) ;
            
            $value = $serializer->unserialize($decrypted_value);
        }
        $event->setResult($result);
    }

    /**
     * On write item pre
     *
     * @param  Event $event
     * @return void
     */
    public function onWriteItemPre(Event $event)
    {
        $serializer = $this->getOptions()->getSerializer();
        $params     = $event->getParams();
        $serialize_value = $serializer->serialize($params['value']);
        
        ### time to decrypt the result
        $encrypt = new data_encryptor() ;
        $encrypt->setDataLockKey(md5($params['key'])) ;
        $encrypted_serialize_value = $encrypt->filter($serialize_value) ;
        
        $params['value'] = $encrypted_serialize_value ;
    }

    /**
     * On write items pre
     *
     * @param  Event $event
     * @return void
     */
    public function onWriteItemsPre(Event $event)
    {
        $serializer = $this->getOptions()->getSerializer();
        $params     = $event->getParams();
        foreach ($params['keyValuePairs'] as &$value) {
        	###
        	$serialize_value = $serializer->serialize($value) ;
        	### time to decrypt the result
        	$encrypt = new data_encryptor() ;
        	$encrypt->setDataLockKey(md5($params['key'])) ;
        	$value = $encrypt->filter($serialize_value) ;        	
        }
    }

   

}
