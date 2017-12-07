<?php

namespace Diatem\ApiDocParser;

use Diatem\ApiDocParser\ApiDocParserConf;
use Diatem\ApiDocParser\ApiDocParserRender;
use Jin2\Utils\StringTools;
use Jin2\Log\Debug;
use Jin2\Com\Curl;
use Jin2\DataFormat\Json;
use Jin2\FileSystem\File;

class ApiDocParserRender{
    public static $rootFolder;
    public static $folder;
    public static $url;
    public static $excludedFiles;
    public static $apiDefineDeclarationFile;
    public static $useJinDump;
    private static $userName;
    private static $userKey;
    private static $jwt;
    const maxSizeDump = 5000;

    /**
     * Initialise un applicatif
     * @param   string  folder                      Chemin relatif vers le dossier à analyser
     * @param   string  restUrl                     Url de la racine des services REST
     * @param   string  userName                    Nom d'utilisateur (pour authentification - si null pas d'authentification)
     * @param   string  userKey                     Clé utilisateur (pour authentification - si null pas d'authentification)
     * @param   string  apiDefineDeclarationFile    Chemin absolu du fichier des déclarations @apiDefine
     * @param   array   excludedFiles               Fichiers exclus de l'analyse
     * @param   boolean useJinDump                  Utilisation de JIN pour les dumps de variables (false par défaut)
     */
    public static function init($folder, $restUrl, $userName = null, $userKey = null, $apiDefineDeclarationFile = null, $excludedFiles = array(), $useJinDump = false){
        self::$folder = $folder;

        self::$rootFolder = StringTools::replaceFirst(__FILE__, 'vendor/diatem-net/apidocparser/src/Diatem/ApiDocParser/ApiDocParserRender.php', '');
        self::$folder = self::$rootFolder.$folder;
        self::$url = $restUrl;
        self::$excludedFiles = $excludedFiles;
        self::$apiDefineDeclarationFile = $apiDefineDeclarationFile;
        self::$userName = $userName;
        self::$userKey = $userKey;
        self::$useJinDump = $useJinDump;
        if(isset($_REQUEST['reload'])){
            ApiDocParserConf::load(true);
            header('Location: '.$_SERVER['PHP_SELF']);
        }else{
            ApiDocParserConf::load();
        }
        self::rooting();
    }

    private static function rooting(){
        echo '<html>';
        echo '<body>';

        self::render_css();
        if(isset($_REQUEST['endpoint']) && isset($_REQUEST['method'])){
            self::render_method($_REQUEST['endpoint'], $_REQUEST['method']);
            
        }else if(isset($_REQUEST['endpoint'])){
            self::render_endpoint($_REQUEST['endpoint']);
        }else{
            self::render_root();
        }

        echo '</body>';
        echo '</html>';
    }

    private static function render_root(){
    
        echo '<div class="head">';
        echo '<div class="infos">';
        echo '<h1>Endpoints</h1>';
        echo '<a href="?reload=1">recharger le cache</a>';
        echo '</div>';
        echo '<div class="liens">';
        echo '<a href="'.$_SERVER['PHP_SELF'].'">endpoints</a>';
        echo '</div>';
        echo '</div>';

        echo '<div class="col1">';
        foreach(ApiDocParserConf::$conf AS $endpointName => $endpoint){
            echo '<div class="bloc">';
            echo '<h2><a href="?endpoint='.$endpointName.'">'.$endpoint['name'].'</a></h2>';

            foreach($endpoint['methods'] AS $methodId => $method){
                echo '<div class="method">';
                    echo '<a href="?endpoint='.$endpointName.'&method='.$methodId.'"><b>'.StringTools::toUpperCase($method['method']).'</b> - '.$method['url'].'</a>';
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    private static function render_endpoint($endpoint){
        $endpointData = ApiDocParserConf::$conf[$endpoint];

        echo '<div class="head">';
        echo '<div class="infos">';
        echo '<h1>'.$endpointData['name'].'</h1>';
        echo '</div>';
        echo '<div class="liens">';
        echo '<a href="'.$_SERVER['PHP_SELF'].'">endpoints</a> / <a href="?endpoint='.$endpoint.'">'.$endpointData['name'].'</a>';
        echo '</div>';
        echo '</div>';

        echo '<div class="col1">';
        echo '<div class="bloc">';
        foreach($endpointData['methods'] AS $methodId => $method){
            echo '<div class="method">';
                 echo '<a href="?endpoint='.$endpointData['name'].'&method='.$methodId.'"><b>'.StringTools::toUpperCase($method['method']).'</b> - '.$method['url'].'</a>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    private static function render_method($endpoint, $methodId){
        $endpointData = ApiDocParserConf::$conf[$endpoint];
        $methodData = $endpointData['methods'][$methodId];

        echo '<div class="head">';
        echo '<div class="infos">';
        echo '<h1>'.$endpointData['name'].'</h1>';
        echo '<h2>'.StringTools::toUpperCase($methodData['method']).' - '.$methodData['url'].'</h2>';
        echo '</div>';
        echo '<div class="liens">';
        echo '<a href="'.$_SERVER['PHP_SELF'].'">endpoints</a> / <a href="?endpoint='.$endpoint.'">'.$endpointData['name'].'</a> / <a href="?endpoint='.$endpoint.'&method='.$methodId.'">'.StringTools::toUpperCase($methodData['method']).' - '.$methodData['url'].'</a>';
        echo '</div>';
        echo '</div>';


        echo '<div class="container">';
        echo '<div class="col1">';

        echo '<form method="POST" action="" enctype="multipart/form-data">';
        echo '<input type="hidden" name="send" value="1">';

        if(count($methodData['urlargs']) > 0){
            echo '<div class="url bloc">';
                echo '<h2>Url</h2>';

                $url = $methodData['url'];
                foreach($methodData['urlargs'] AS $arg){
                    $valeur = '';
                    if(isset($_POST['url?'.$arg])){
                        $valeur = $_POST['url?'.$arg];
                    }
                    $url = StringTools::replaceFirst($url, $arg, '<input type="text" value="'.$valeur.'" name="url?'.$arg.'">');
                }
                echo '<div class="compose">';
                    echo $url;
                echo '</div>';
            echo '</div>';
        }

        if(count($methodData['arguments']) > 0){
            echo '<div class="arguments bloc">';
                echo '<h2>Arguments</h2>';

                foreach($methodData['arguments'] AS $argument){
                    
                    $addClass = '';
                    if(!$argument['optionnel']){
                        $addClass = 'required';
                    }

                    echo '<div class="argument '.$addClass.'"">';

                    echo '<div class="def">';
                        echo '<div class="nom">'.$argument['nom'].'</div>';
                        echo '<div class="type">'.$argument['type'].'</div>';
                    echo '</div>';

                    echo '<div class="valeur">';
                    $type = StringTools::toLowerCase($argument['type']);
                    $valeur = $argument['defaut'];
                    if(isset($_POST['argument?'.$argument['nom']])){
                        $valeur = $_POST['argument?'.$argument['nom']];
                    }

                    if($type == 'string'){
                        echo '<input type="text" name="argument?'.$argument['nom'].'" value="'.$valeur.'">';
                    }elseif($type == 'file'){
                            echo '<input type="file" name="argument?'.$argument['nom'].'" value="'.$valeur.'">';
                    }elseif($type == 'datetime'){
                            echo '<input type="datetime-local" name="argument?'.$argument['nom'].'" value="'.$valeur.'">';
                    }elseif($type == 'date'){
                        echo '<input type="date" name="argument?'.$argument['nom'].'" value="'.$valeur.'">';
                    }else if($type == 'integer'){
                        echo '<input type="text" name="argument?'.$argument['nom'].'" value="'.$valeur.'">';
                    }else if($type == 'boolean'){
                        echo '<select name="argument?'.$argument['nom'].'">';
                        echo '<option value="" '.$selected.'>NULL</option>';
                            $selected = '';
                            if($valeur === true || $valeur === 1 || $valeur === 'true' || $valeur === 'TRUE' || $valeur === '1'){ $selected='selected="selected"'; }

                            echo '<option value="1" '.$selected.'>TRUE</option>';
                            $selected = '';
                            if($valeur === false || $valeur === 0 || $valeur === 'false' || $valeur === 'FALSE' || $valeur === '0'){ $selected='selected="selected"'; }
                            echo '<option value="0" '.$selected.'>FALSE</option>';
                        echo '</select>';
                    }else if($type == 'array'){
                        echo '<textarea name="argument?'.$argument['nom'].'">'.$valeur.'</textarea>';
                        echo '<div class="help">Intégrez le tableau au format JSON</div>';
                    }
                    echo '</div>';

                    if(!$argument['optionnel']){
                        echo '<div class="opt">(requis)</div>';
                    }

                    echo '</div>';
                }
            echo '</div>';
        }

        echo '<div class="launch">';
            echo '<input type="submit" name="Envoyer la requête">';
        echo '</div>';

        echo '</form>';

        echo '</div>';
        echo '<div class="col2">';
            if(isset($_REQUEST['send'])){
                if(self::$userKey){
                    self::connect();
                }
                self::render_result($_REQUEST['endpoint'], $_REQUEST['method']);
            }
        echo '</div>';
        echo '</div>';
    }

    private static function render_result($endpoint, $methodId){
        $endpointData = ApiDocParserConf::$conf[$endpoint];
        $methodData = $endpointData['methods'][$methodId];

        $url = self::$url.$methodData['url'];
        foreach($methodData['urlargs'] AS $urlarg){
            $url = StringTools::replaceFirst($url, $urlarg, $_REQUEST['url?'.$urlarg]);
        }
        if(StringTools::right($url, 1) == '/'){
            $url = StringTools::left($url, StringTools::len($url)-1);
        }
        
        $args = array();
        foreach($methodData['arguments'] AS $arg){
            if($arg['type'] == 'array'){
                $def = array();
                $json = Json::decode($_REQUEST['argument?'.$arg['nom']]);
                if(is_array($json)){
                    $args[$arg['nom']] = $json;
                }
            }elseif($arg['type'] == 'file'){
                $fData = $_FILES['argument?'.$arg['nom']];
                $f = new File($fData['tmp_name']);
                $args[$arg['nom']] = array(
                    'fileName'      =>  $fData['name'],
                    'fileContent'   =>  base64_encode($f->getBlob())
                );
            }elseif($arg['type'] == 'datetime'){
                $dt = new \DateTime($_REQUEST['argument?'.$arg['nom']]);
                $args[$arg['nom']] = $dt->format('d/m/Y h:i:s');
            }elseif($arg['type'] == 'date'){
                $dt = new \DateTime($_REQUEST['argument?'.$arg['nom']]);
                $args[$arg['nom']] = $dt->format('d/m/Y');
            }else{
                $args[$arg['nom']] = $_REQUEST['argument?'.$arg['nom']];
            }
            
        }
        
        if(StringTools::toLowerCase($methodData['method']) == 'get'){
            $requestType = Curl::CURL_REQUEST_TYPE_GET;
        }else if(StringTools::toLowerCase($methodData['method']) == 'post'){
            $requestType = Curl::CURL_REQUEST_TYPE_POST;
        }else if(StringTools::toLowerCase($methodData['method']) == 'patch'){
            $requestType = Curl::CURL_REQUEST_TYPE_PATCH;
        }else if(StringTools::toLowerCase($methodData['method']) == 'delete'){
            $requestType = Curl::CURL_REQUEST_TYPE_DELETE;
        }else if(StringTools::toLowerCase($methodData['method']) == 'put'){
            $requestType = Curl::CURL_REQUEST_TYPE_PUT;
        }

        echo '<div class="call">';

        echo '<div class="url bloc"><h2>Url appelée</h2>'.$url.'</div>';

        echo '<div class="code bloc"><h2>Arguments</h2>';
        echo '<pre>';
        self::dump($args);
        echo '</pre>';
        echo '</div>';
        
        $throwErrors = true;
        $httpAuthUser = null;
        $httpAuthPassword = null;
        $contentType = null;
        $headers = array();

        if(self::$userKey){
            $headers['Authorization'] = self::$jwt;
        }
        
        $outputTraceFile = 'log.txt';
        $followLocation = false;
        
        $res = Curl::call( $url, 
                    $args, 
                    $requestType,
                    $throwErrors, 
                    $httpAuthUser, 
                    $httpAuthPassword, 
                    $contentType, 
                    $headers, 
                    $outputTraceFile, 
                    $followLocation );
        

        echo '<div class="code bloc"><h2>Code HTTP de retour</h2>'.Curl::getLastHttpCode().'</div>';
        
        $json = json_decode($res);
        if($json){
            echo '<div class="code bloc"><h2>Retour JSON</h2>';
            echo '<pre>';
            self::dump($json);
            echo '</pre>';
            echo '</div>';
        }else{
            echo '<div class="code bloc"><h2>Erreur d\'execution</h2>';
            echo '<pre>';
            self::dump($res);
            echo '</pre>';
            echo '</div>';
        }

        echo '</div>';
        
    }

    private static function connect(){
        $url = self::$url.'login';
        
        $args = array(
            'userID'  =>  self::$userName,
            'userKey' =>   self::$userKey
        );
        $requestType = Curl::CURL_REQUEST_TYPE_POST;
        $throwErrors = true;
        $httpAuthUser = null;
        $httpAuthPassword = null;
        $contentType = null;
        $headers = array();
        $outputTraceFile = 'log.txt';
        $followLocation = false;
        
        $res = Curl::call( $url, 
                    $args, 
                    $requestType,
                    $throwErrors, 
                    $httpAuthUser, 
                    $httpAuthPassword, 
                    $contentType, 
                    $headers, 
                    $outputTraceFile, 
                    $followLocation );
        
        $res = json_decode($res, true);
        if(!isset($res['jwt'])){
            echo '<div class="erreur">Connexion impossible !</div>';
            Debug::dump($res);
        }
        self::$jwt = $res['jwt'];
        
    }

    private static function render_css(){
        if(is_file(dirname($_SERVER['SCRIPT_FILENAME']).'/style.css')){
            $rel = StringTools::replaceFirst(dirname($_SERVER['SCRIPT_FILENAME']).'/style.css', self::$rootFolder, '');
            echo '<link href="/'.$rel.'" rel="stylesheet" type="text/css" />';
        }else{
            echo '<link href="/vendor/diatem-net/apidocparser/css/style.css" rel="stylesheet" type="text/css" />';
        } 
    }

    private static function dump($var){
        if(self::$useJinDump){
            Debug::dump($var, self::maxSizeDump);
        }else{
            var_dump($var);
        }
    }
}