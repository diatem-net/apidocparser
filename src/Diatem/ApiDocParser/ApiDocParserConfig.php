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
    public static $version = 'v1.1.0';
    public static $projectUrl = 'https://packagist.org/packages/diatem-net/apidocparser';
    public static $themeFile = 'style_black.css';

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
}