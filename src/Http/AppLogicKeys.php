<?php
declare(strict_types=1);

namespace DV\Http;


trait AppLogicKeys
{
    /**
     * Hold the Hash of required for Authenticated Request
     * @var string
     */
    public static $X_AUTH_USER = 'X-Auth-User' ;

    /**
     * Will signify that error has occured on a Auth Required Request
     * @var string
     */
    public static $X_AUTH_ERROR = 'X-Auth-Error' ;

    /**
     * Will signify that error has occured during Login/Auth Request
     * @var string
     */
    public static $X_LOGIN_ERROR = 'X-Login-Error' ;

    /**
     * Will signify that error has occured during ACL Logic
     * @var string
     */
    public static $X_ACL_ERROR = 'X-Acl-Error' ;

    /**
     * Will signify that a requirement is missing to complete the current request
     * @var string
     */
    public static $X_REQ_ERROR = 'X-Req-Error' ;

    public static $X_RSA_ID = 'X-Rsa-Id' ;

    public static $X_RSA_SERVER_ID = 'X-Rsa-Server-Id' ;

    public static $X_REDIRECT_TO = 'X-Redirect-To' ;

    public static $X_PLATFORM = 'X-Platform' ;

    public static $X_USERNAME = 'X-Username' ;

    public static $X_TOKEN = 'X-Token' ;

    public static $X_TOKEN_REQ_TIME = 'X-Token-Req-Time' ;

    public static $X_TOKEN_VALIDITY = 'X-Token-Validity' ;

    public static $AUTHORIZATION = 'Authorization' ;

    public static $AUTHENTICATION = 'Authentication' ;

    /**
     * Hold the data for current Authenticated user during Login logic
     * @var string
     */
    public static $X_AUTHENTICATED_USER = 'X-Authenticated-User' ;

    /**
     * Hold the data for authenticated token value that is sent e.g Token sent from JWT client
     * @var string
     */
    public static $X_AUTHENTICATED_HEADER_TOKEN = 'X-Authenticated-Header-Token' ;

    /**
     * Hold the data for already decrypted token value that is sent e.g Token sent from JWT client
     * @var string
     */
    public static $X_JWT_CLIENT_REQUEST_TOKEN = 'X-JWT-Client-Request-Token' ;


    /**
     * Hold the data for already decrypted token value that is sent e.g Token sent from JWT client
     * @var string
     */
    public static $X_DECRYPTED_HEADER_TOKEN = 'X-Decrypted-Header-Token' ;

    /**
     * Hold the data for api key for  link that has no need for Username/Password Authentication
     * @var string
     */
    public static $X_API_KEY = 'x-api-key' ;

    /**
     * Hold the data for api key for  link that has no need for Username/Password Authentication
     * @var string
     */
    public static $X_CLIENT_LICENSE_TOKEN = 'x-client-license-token' ;

    /**
     * Hold all the messages that will be passed to FlashMessager or Json Model
     * @var string
     */
    public static $APP_MESSAGE_NOTIFIER = 'AppMessageNotifier' ;

    /**
     * we expect that request to this api should come from a Web App or Native App (Desktop, Mobile e.g Android, iOS, Blackberry e.t.c)
     * @var array
     */
    public static $SUPPORTED_PLATFORM = ['web' , 'native'] ;

    public static $KEY_ALLOW_FULL_HEADER_METHOD = 'GET,HEAD,POST,DEBUG,PUT,DELETE,PATCH,OPTIONS' ;
    public static $KEY_ALLOW_FULL_HEADER = 'Origin,Keep-Alive,User-Agent,X-Requested-With,X-Requested-By,Cache-Control,Content-Type,Content-Range,Range,Accept';
}