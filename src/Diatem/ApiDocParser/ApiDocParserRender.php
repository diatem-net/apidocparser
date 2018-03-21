<?php

namespace Diatem\ApiDocParser;

use Diatem\ApiDocParser\ApiDocParserLoader;
use Diatem\ApiDocParser\ApiDocParserRender;
use Diatem\ApiDocParser\ApiDocParserConfig;
use Jin2\Utils\StringTools;
use Jin2\Log\Debug;
use Jin2\Com\Curl;
use Jin2\DataFormat\Json;
use Jin2\FileSystem\File;

class ApiDocParserRender{
    

    /**
     * Initialise un applicatif
     * @param   string  folder                      Chemin relatif vers le dossier à analyser
     * @param   string  restUrl                     Url de la racine des services REST
     */
    public static function init($folder, $restUrl){
        
        ApiDocParserConfig::setUrl($restUrl);
        ApiDocParserConfig::setRootFolder(StringTools::replaceFirst(__FILE__, 'vendor/diatem-net/apidocparser/src/Diatem/ApiDocParser/ApiDocParserRender.php', ''));
        ApiDocParserConfig::setFolder(ApiDocParserConfig::$rootFolder.$folder);
       
        if(isset($_REQUEST['reload'])){
            ApiDocParserLoader::load(true);
            header('Location: '.$_SERVER['PHP_SELF']);
        }else{
            ApiDocParserLoader::load();
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
        echo '<div class="infos_singleLine">';
        echo '<h1>Endpoints</h1>';
        echo '</div>';
        echo '<div class="liens">';
        echo '<a href="'.$_SERVER['PHP_SELF'].'">endpoints</a>';
        echo '</div>';
        echo '</div>';

        self::renderFooter();

        echo '<div class="col1_full">';
        foreach(ApiDocParserLoader::$conf AS $endpointName => $endpoint){
            echo '<div class="bloc">';
            echo '<h2 class="endpointName method"><a href="?endpoint='.$endpointName.'">'.$endpoint['name'].'</a></h2>';

            foreach($endpoint['methods'] AS $methodId => $method){
                echo '<div class="linemethod">';
                    echo '<a href="?endpoint='.$endpointName.'&method='.$methodId.'"><div class="method '.StringTools::toUpperCase($method['method']).'">'.StringTools::toUpperCase($method['method']).'</div><div class="methodName">'.$method['url'].'</div></a>';
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    private static function render_endpoint($endpoint){
        $endpointData = ApiDocParserLoader::$conf[$endpoint];

        echo '<div class="head">';
        echo '<div class="infos_singleLine">';
        echo '<h1>'.$endpointData['name'].'</h1>';
        echo '</div>';
        echo '<div class="liens">';
        echo '<a href="'.$_SERVER['PHP_SELF'].'">endpoints</a> / <a href="?endpoint='.$endpoint.'">'.$endpointData['name'].'</a>';
        echo '</div>';
        echo '</div>';

        self::renderFooter();

        echo '<div class="col1_full">';
        echo '<div class="bloc">';
        foreach($endpointData['methods'] AS $methodId => $method){
            echo '<div class="linemethod">';
                 echo '<a href="?endpoint='.$endpointData['name'].'&method='.$methodId.'"><div class="method '.StringTools::toUpperCase($method['method']).'">'.StringTools::toUpperCase($method['method']).'</div><div class="methodName">'.$method['url'].'</div></a>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    private static function render_method($endpoint, $methodId){
        $endpointData = ApiDocParserLoader::$conf[$endpoint];
        $methodData = $endpointData['methods'][$methodId];

        echo '<div class="head">';
        echo '<div class="infos">';
        echo '<h1>'.$endpointData['name'].'</h1>';
        echo '<h2><div class="method '.StringTools::toUpperCase($methodData['method']).'" >'.StringTools::toUpperCase($methodData['method']).'</div> '.$methodData['url'].'</h2>';
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
                if(ApiDocParserConfig::$userKey){
                    self::connect();
                }
                self::render_result($_REQUEST['endpoint'], $_REQUEST['method']);
            }
        echo '</div>';
        echo '</div>';
    }

    private static function render_result($endpoint, $methodId){
        $endpointData = ApiDocParserLoader::$conf[$endpoint];
        $methodData = $endpointData['methods'][$methodId];

        $url = ApiDocParserConfig::$url.$methodData['url'];
        foreach($methodData['urlargs'] AS $urlarg){
            $url = StringTools::replaceFirst($url, $urlarg, $_REQUEST['url?'.$urlarg]);
        }
        if(ApiDocParserConfig::$useSlashEnd){
            if(StringTools::right($url, 1) != '/'){
                $url .= '/';
            }
        }
        
        
        $args = array();
        foreach($methodData['arguments'] AS $arg){
            if($arg['type'] == 'integer'){
                if($_REQUEST['argument?'.$arg['nom']] === ''){
                   
                }else{
                    $args[$arg['nom']] =  $_REQUEST['argument?'.$arg['nom']];
                }
                
            }else if($arg['type'] == 'boolean'){
                if($_REQUEST['argument?'.$arg['nom']] == ''){
                    //$args[$arg['nom']] = null;
                }else{
                    $args[$arg['nom']] = $_REQUEST['argument?'.$arg['nom']];
                }
            }else if($arg['type'] == 'string'){
                if($_REQUEST['argument?'.$arg['nom']] == ''){
                    //$args[$arg['nom']] = null;
                }else{
                    $args[$arg['nom']] = $_REQUEST['argument?'.$arg['nom']];
                }
            }elseif($arg['type'] == 'array'){
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
                if(!empty($_REQUEST['argument?'.$arg['nom']])){
                    $dt = new \DateTime($_REQUEST['argument?'.$arg['nom']]);
                    $args[$arg['nom']] = $dt->format('d/m/Y h:i:s');
                }else{
                    //$args[$arg['nom']] = null;
                }
            }elseif($arg['type'] == 'date'){
                if(!empty($_REQUEST['argument?'.$arg['nom']])){
                    $dt = new \DateTime($_REQUEST['argument?'.$arg['nom']]);
                    $args[$arg['nom']] = $dt->format('d/m/Y');
                }else{
                    //$args[$arg['nom']] = null;
                }
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

        echo '<div class="url bloc"><h2>Url appelée</h2><div class="method '.StringTools::toUpperCase($methodData['method']).'">'.StringTools::toUpperCase($methodData['method']).'</div>  <div class="method">'.$url.'</div></div>';

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

        if(ApiDocParserConfig::$userKey){
            $headers['Authorization'] = ApiDocParserConfig::$jwt;
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
        

        echo '<div class="code bloc"><h2>Code HTTP de retour</h2><div class="method code code_'.Curl::getLastHttpCode().'"><b>'.Curl::getLastHttpCode().'</b></div></div>';
        
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
        $url = ApiDocParserConfig::$url.ApiDocParserConfig::$loginEndpoint;

        $args = array(
            ApiDocParserConfig::$loginEndpointUserNameAttribute  =>  ApiDocParserConfig::$userName,
            ApiDocParserConfig::$loginEndpointUserKeyAttribute =>   ApiDocParserConfig::$userKey
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
        }else{
            ApiDocParserConfig::$jwt = $res['jwt'];
        }
    
    }

    private static function render_css(){
        if(is_file(dirname($_SERVER['SCRIPT_FILENAME']).'/style.css')){
            $rel = StringTools::replaceFirst(dirname($_SERVER['SCRIPT_FILENAME']).'/style.css', ApiDocParserConfig::$rootFolder, '');
            echo '<link href="/'.$rel.'" rel="stylesheet" type="text/css" />';
        }else{
            echo '<link href="/vendor/diatem-net/apidocparser/css/'.ApiDocParserConfig::$themeFile.'" rel="stylesheet" type="text/css" />';
        } 
    }

    private static function renderFooter(){
        echo '<div class="footer">';
        echo '<div class="appInfo method"><a target="_blank" href="'.ApiDocParserConfig::$projectUrl.'">apidocparser '.ApiDocParserConfig::$version.'</a></div> ';
        echo '<div class="reload"><a href="?reload=1">recharger le cache</a></div>';
        echo '</div>';
    }

    private static function dump($var){
        if(ApiDocParserConfig::$useJinDump){
            Debug::dump($var, ApiDocParserConfig::$maxSizeDump);
        }else{
            echo '<div class="intDump">';
            var_dump($var);
            echo '</div>';
        }
    }
}