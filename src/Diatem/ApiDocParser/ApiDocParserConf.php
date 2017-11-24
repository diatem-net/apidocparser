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


class ApiDocParserConf{
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

    private static function getDatasFromFiles(){
        $excluedFiles = array('index.php');
        
        $endpoints = array();
        $apidefine = array();
        
        //Récupère les fichiers à analyser
        $folder = new Folder(ApiDocParserRender::$folder, 'php');
        $files = array();
        $defineFileName = null;
        if(ApiDocParserRender::$apiDefineDeclarationFile){
            $files[] = array(
                'type'  =>  'apidefine',
                'file'  =>  ApiDocParserRender::$rootFolder.ApiDocParserRender::$apiDefineDeclarationFile
            );
            $f = new File(ApiDocParserRender::$rootFolder.ApiDocParserRender::$apiDefineDeclarationFile);
            $defineFileName = $f->getFileName();
        }
        foreach($folder AS $f){
            if((!$defineFileName || $f != $defineFileName) && ArrayTools::find(ApiDocParserRender::$excludedFiles, $f) === false){
                $files[] = array(
                    'type'  =>  'endpoint',
                    'file'  =>  $f
                );
            }
        }
        
        foreach($files AS $f){

            if($f['type'] == 'apidefine'){
                $endPointName = StringTools::replaceLast($f['file'], '.php','');
                $endpoint = array(
                    'name'  =>  $endPointName,
                    'methods'   =>  array()
                );

                $file = new File($f['file']);
                $content = $file->getContent();

                $matches = array();
                preg_match_all("/\/\*\*+(.*?)+\*\//s", $content, $matches, PREG_SET_ORDER);


                foreach($matches AS $match){
                    $mContent = $match[0];
                    
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
                                    $argument['type'] = str_replace(array('{','}'),'',$lm[2+$dec][0]);
        
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
                $endPointName = StringTools::replaceLast($f['file'], '.php','');
                $endpoint = array(
                    'name'  =>  $endPointName,
                    'methods'   =>  array()
                );

                $file = new File(ApiDocParserRender::$folder.$f['file']);
                $content = $file->getContent();

                $matches = array();
                preg_match_all("/\/\*\*+(.*?)+\*\//s", $content, $matches, PREG_SET_ORDER);


                foreach($matches AS $match){
                    $mContent = $match[0];
                    
                    if(StringTools::contains($mContent, '@api')){
                        $method = array(
                            'method'        =>  '',
                            'url'           =>  '',
                            'arguments'     =>  array(),
                            'urlargs'       =>  array()
                        );
        
                        $lines = explode("\n", $mContent);
                        foreach($lines AS $line){
                            $lm = array();
                            preg_match_all("/(\S+)/", $line, $lm, PREG_SET_ORDER);
        
                            if(count($lm) >= 2){
                                if($lm[1][0] == '@apiParam'){
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
                                    $argument['type'] = str_replace(array('{','}'),'',$lm[2+$dec][0]);
        
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
        
                        $id = 'm'.uniqid();
                        $endpoint['methods'][$id] = $method;
                        
                    } 
                }

                $endpoints[$endpoint['name']] = $endpoint;
            }
               
        }
        
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