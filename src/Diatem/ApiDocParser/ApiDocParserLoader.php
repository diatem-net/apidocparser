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


class ApiDocParserLoader{
    public static $conf;
    
    public static function load($force = false){
        if($force){
            self::getDatasFromFiles();
        }else{
            if(is_file('__genconf')){
                if(!self::getDatasFromCache()){
                    self::getDatasFromFiles();
                }
            }else{
                self::getDatasFromFiles();
            }
            
        }
    }


    private static function recursiveFolderAnalyse($folderPath, $files, $defineFileName){
        if(StringTools::right($folderPath, 1) != '/'){
            $folderPath .= '/';
        }

        //Récupère les fichiers à analyser
        $folder = new Folder($folderPath);
        
        foreach($folder AS $f){
            if(is_dir($folderPath.$f)){
                $files = self::recursiveFolderAnalyse($folderPath.$f.'/', $files, $defineFileName);
            }else if(StringTools::right($f,3) == 'php'){
                if((!$defineFileName || $f != $defineFileName) && ArrayTools::find(ApiDocParserConfig::$excludedFiles, $f) === false){
                    $files[] = array(
                        'type'  =>  'endpoint',
                        'file'  =>  StringTools::replaceAll($folderPath, ApiDocParserConfig::$folder, '').$f
                    );
                }
            }
        }

        return $files;
    }

    private static function getDatasFromFiles(){
        $excluedFiles = array('index.php');
        
        $endpoints = array();
        $apidefine = array();
        
        $files = array();

        //Définition
        $defineFileName = null;
        if(ApiDocParserConfig::$apiDefineDeclarationFile){
            $files[] = array(
                'type'  =>  'apidefine',
                'file'  =>  ApiDocParserConfig::$rootFolder.ApiDocParserConfig::$apiDefineDeclarationFile
            );
            $f = new File(ApiDocParserConfig::$rootFolder.ApiDocParserConfig::$apiDefineDeclarationFile);
            $defineFileName = $f->getFileName();
        }

        $files = self::recursiveFolderAnalyse(ApiDocParserConfig::$folder, $files, $defineFileName);


        foreach($files AS $f){

            if($f['type'] == 'apidefine'){
                $endPointName = ListTools::last($f['file'], '/');
                $endPointName = StringTools::replaceLast($endPointName, '.php','');
                $endpoint = array(
                    'name'  =>  $endPointName,
                    'methods'   =>  array()
                );

                $file = new File($f['file']);
                $content = $file->getContent();

                $matches = array();
                preg_match_all("/\/\*\*(.*?)\*\//s", $content, $matches);


                foreach($matches[0] AS $match){
                    $mContent = $match;

                    
                    
                    if(StringTools::contains($mContent, '@apiDefine')){
                        $define = true;
                        $globalDefine = array(
                            'arguments' =>  array()
                        );
                        $nom = '';
        
                        $lines = explode("\n", $mContent);
                        foreach($lines AS $line){
                            $lm = array();
                            preg_match_all("/(\S+)/", $line, $lm, PREG_SET_ORDER);
        
                            if(count($lm) >= 2){
                                if($lm[1][0] == '@apiDefine'){
                                    $nom = $lm[2][0];
                                }elseif($lm[1][0] == '@apiParam'){
                                    $argument = array(
                                        'nom'       =>  '',
                                        'type'      =>  '',
                                        'optionnel' =>  false,
                                        'defaut'    =>  null
                                    );
                                    $dec = 0;
                                    if(StringTools::contains($lm[2][0],'(')){
                                        $dec = 1;
                                    }
                                    $argument['type'] = StringTools::toLowerCase(str_replace(array('{','}'),'',$lm[2+$dec][0]));
        
                                    if(StringTools::contains($lm[3+$dec][0], '[')){
        
                                        $argument['optionnel'] = true;
                                        $v = str_replace(array('[',']'),'',$lm[3+$dec][0]);
                                        if(StringTools::contains($v, '=')){
                                            $vs = explode('=', $v);
                                            $argument['defaut'] = $vs[1];
                                            $argument['nom'] = $vs[0];
                                        }else{
                                            
                                            $argument['nom'] = $v;
                                        }
                                   
                                    }else{
                                        $argument['nom'] = $lm[3+$dec][0];
                                    }
                                    
                                    $globalDefine['arguments'][] = $argument;
                                   
                                }
                            }
        
        
                        }
        
                        $apidefine[$nom] = $globalDefine;
        
                    }
                }
            }else if($f['type'] == 'endpoint'){
                


                $file = new File(ApiDocParserConfig::$folder.$f['file']);
                $content = $file->getContent();
                
                $matches = array();
                preg_match_all("/\/\*\*(.*?)\*\//s", $content, $matches);


                foreach($matches[0] AS $match){
                    $mContent = $match;
                    
                    if(StringTools::contains($mContent, '@api')){
                        $endpoint = null;
                        $method = array(
                            'method'        =>  '',
                            'url'           =>  '',
                            'arguments'     =>  array(),
                            'urlargs'       =>  array(),
                            'apiAuthMethod' =>  'none'
                        );
        
                        $lines = explode("\n", $mContent);
                        foreach($lines AS $line){
                            $lm = array();
                            preg_match_all("/(\S+)/", $line, $lm, PREG_SET_ORDER);
        
                            if(count($lm) >= 2){
                                if($lm[1][0] == '@apiAuthMethod'){
                                    $method['apiAuthMethod'] = $lm[2][0];
                                }elseif($lm[1][0] == '@apiGroup'){
                                    $endpoint = $lm[2][0];

                                    if(!isset($endpoints[$endpoint])){
                                        $endpoints[$endpoint] = array(
                                            'name'  =>  $endpoint,
                                            'methods'   =>  array()
                                        );
                                    }
                                }else if($lm[1][0] == '@apiParam'){
                                    $argument = array(
                                        'nom'       =>  '',
                                        'type'      =>  '',
                                        'optionnel' =>  false,
                                        'defaut'    =>  null
                                    );
                                    $dec = 0;
                                    if(StringTools::contains($lm[2][0],'(')){
                                        $dec = 1;
                                    }
                                    $argument['type'] = StringTools::toLowerCase(str_replace(array('{','}'),'',$lm[2+$dec][0]));
        
                                    if(StringTools::contains($lm[3+$dec][0], '[')){
        
                                        $argument['optionnel'] = true;
                                        $v = str_replace(array('[',']'),'',$lm[3+$dec][0]);
                                        if(StringTools::contains($v, '=')){
                                            $vs = explode('=', $v);
                                            $argument['defaut'] = $vs[1];
                                            $argument['nom'] = $vs[0];
                                        }else{
                                            
                                            $argument['nom'] = $v;
                                        }
                                   
                                    }else{
                                        $argument['nom'] = $lm[3+$dec][0];
                                    }
                                    
                                    $method['arguments'][] = $argument;
                                   
                                }else if($lm[1][0] == '@apiUse'){
                                    if(isset($apidefine[$lm[2][0]])){
                                        foreach($apidefine[$lm[2][0]]['arguments'] AS $arg){
                                            $method['arguments'][] = $arg;
                                        }
                                    }
                                }else if($lm[1][0] == '@api'){
                                    $method['method'] = str_replace(array('{','}'),'',$lm[2][0]);
                                    $method['url'] = $lm[3][0];
                                }
                            }
        
        
                        }

                        $lm = array();
                        preg_match_all("/:+([a-z]*)/", $method['url'], $lm, PREG_SET_ORDER);
                        foreach($lm AS $r){
                            $method['urlargs'][] = $r[0];
                        }
        
                        $id =  $method['method'].'-'.$method['url'];
                        
                        $endpoints[$endpoint]['methods'][$id] = $method;
                        
                    } 
                }

            }
               
        }


        ksort($endpoints);

        $out = new File('__genconf', true);
        $out->write(Json::encode($endpoints));

        self::$conf = $endpoints;
    }

    private static function getDatasFromCache(){
        try{
            $f = new File('__genconf');
            self::$conf = Json::decode($f->getContent());
            return true;
        }catch(Exception $e){
            return false;
        }
        
    }
}