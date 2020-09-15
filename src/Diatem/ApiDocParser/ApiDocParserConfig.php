<?php

namespace Diatem\ApiDocParser;

use Diatem\ApiDocParser\ApiDocParserRender;
use Jin2\FileSystem\Folder;
use Jin2\FileSystem\File;
use Jin2\Utils\ArrayTools;
use Jin2\Utils\StringTools;
use Jin2\Utils\ListTools;
use Jin2\Log\Debug;
use Jin2\DataFormat\Json;


class ApiDocParserConfig{
    public static $rootFolder;
    public static $folder;
    public static $url;
    public static $excludedFiles = array();
    public static $apiDefineDeclarationFile;
    public static $recursiveAnalyse = false;
    public static $useJinDump = false;
    public static $useSlashEnd = false;
    public static $userName;
    public static $userKey;
    public static $jwt;
    public static $maxSizeDump = 5000;
    public static $loginEndpoint = 'login';
    public static $loginEndpointUserNameAttribute = 'userID';
    public static $loginEndpointUserKeyAttribute = 'userKey';
    public static $version = 'v1.5.0';
    public static $projectUrl = 'https://packagist.org/packages/diatem-net/apidocparser';
    public static $themeFile = 'style_black.css';
    public static $relativeStylePath = null;
    public static $parserUrl = null;
    public static $bearerEndpoint = null;
    public static $bearerArguments = array();
    public static $bearerTokenReturnStructure = '';
    public static $bearerEndpointCallType = 'POST';
    public static $bearerEndpointHeaders = array();
    public static $authentificateAllowedMethods = array('Basic', 'Bearer', 'None', 'Inherit');
    public static $authentificatePreferedMethod = 'basic';
    public static $jsonFormatOutputEnabled = false;
    public static $jsonFormatOutputRootPath = '';

    public static function getAuthentificateMethodName($method){
        if($method == 'None'){
            return 'Aucune';
        }else if($method == 'Basic'){
            return 'Basic';
        }else if($method == 'Bearer'){
            return 'Bearer';
        }else if($method == 'Inherit'){
            return 'Ancienne versions Diatem RestServer (depreciated)';
        }
    }
    
    public static function setAuthentificateAllowedMethods($methods){
        self::$authentificateAllowedMethods = $methods;
    }

    public static function setAuthentificatePreferedMethod($method){
        self::$authentificatePreferedMethod = $method;
    }

    /**
     * Définit les paramètres d'accès au endpoint permettant de créer un token Bearer d'authentification
     * @param   string  $endpoint               Url complète d'accès au endpoint (absolue)
     * @param   string  $endpointCallType       Méthode (POST, GET, PUT ou PATCH)
     * @param   array   $arguments              Arguments (tableau associatif)
     * @param   string  $tokenReturnStructure   Chemin pour trouver le token dans la structure de retour (utiliser '/' comme définissant un niveau inférieur)
     * @param   array   $bearerEndpointHeaders  Headers transmis (tableau associatif, utilisé par exemple pour transmettre une authentification de type "Basic")
     */
    public static function setBearerEndpoint($endpoint, $endpointCallType, $arguments, $tokenReturnStructure, $bearerEndpointHeaders){
        self::$bearerEndpoint = $endpoint;
        self::$bearerArguments = $arguments;
        self::$bearerTokenReturnStructure = $tokenReturnStructure;
        self::$bearerEndpointCallType = $endpointCallType;
        self::$bearerEndpointHeaders = $bearerEndpointHeaders;
    }
    
    public static function setParserUrl($parserUrl){
        self::$parserUrl = $parserUrl;
    }

    public static function setRelativeStylePath($relativeStylePath){
        self::$relativeStylePath = $relativeStylePath;
    }

    public static function setLoginEndpointUserNameAttribute($attributeName){
        self::$loginEndpointUserNameAttribute = $attributeName;
    }

    public static function setLoginEndpointUserKeyAttribute($attributeName){
        self::$loginEndpointUserKeyAttribute = $attributeName;
    }

    public static function setRootFolder($rootFolder){
        self::$rootFolder = $rootFolder;
    }

    public static function setLoginEndpoint($loginEndpoint){
        self::$loginEndpoint = $loginEndpoint;
    }

    public static function setFolder($folder){
        self::$folder = $folder;
    }

    public static function setUrl($url){
        self::$url = $url;
    }

    public static function setExcludedFiles($excludedFiles){
        self::$excludedFiles = $excludedFiles;
    }

    public static function setApiDefineDeclarationFile($apiDefineDeclarationFile){
        self::$apiDefineDeclarationFile = $apiDefineDeclarationFile;
    }

    public static function setRecursiveAnalyse($recursiveAnalyse){
        self::$recursiveAnalyse = $recursiveAnalyse;
    }

    public static function setUseJinDump($useJinDump){
        self::$useJinDump = $useJinDump;
    }

    public static function setUseSlashEnd($useSlashEnd){
        self::$useSlashEnd = $useSlashEnd;
    }

    public static function setUserName($userName){
        self::$userName = $userName;
    }

    public static function setUserKey($userKey){
        self::$userKey = $userKey;
    }

    public static function setJWT($jwt){
        self::$jwt = $jwt;
    }

    public static function setStyle($style){
        self::$themeFile = $style;
    }

    public static function setMaxSizeDump($maxSizeDump){
        self::$maxSizeDump = $maxSizeDump;
    }

    public static function setJsonFormatOutputEnabled($jsonFormatOutputEnabled){
        self::$jsonFormatOutputEnabled = $jsonFormatOutputEnabled;
    }

    public static function setJsonFormatOutputRootPath($jsonFormatOutputRootPath){
        self::$jsonFormatOutputRootPath = $jsonFormatOutputRootPath;
    }
}