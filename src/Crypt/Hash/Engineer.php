<?php
namespace DV\Crypt\Hash;


class Engineer
{

    public static function hmac($string , $options=[])
    {
        if(! isset($options['algo']))    {
            $options['algo'] = 'ripemd256';
        }
        if(! isset($options['secret']))    {
            $options['secret'] = openssl_random_pseudo_bytes(160);
        }

        return hash_hmac($options['algo'], $string , $options['secret']);
    }
}