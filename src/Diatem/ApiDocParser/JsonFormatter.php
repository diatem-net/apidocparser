<?php

namespace Diatem\ApiDocParser;


class JsonFormatter{
    
    private $format;
    private $object;

    public function __construct($json)
    {
        $this->object = json_decode($json, true);
        $this->format = array();
    }

    public function convert(){
        $this->format = $this->analyseObject($this->object);

        return $this->format;
    }

    private function analyseNode($node){
        $infoNode = array();

        if(is_string($node)){
            $infoNode = $this->analyseString($node);
        }else if(is_numeric($node)){
            $infoNode = $this->analyseNumeric($node);
        }else if(is_bool($node)){
            $infoNode = $this->analyseBoolean($node);
        }else if(is_array($node)){
            if(array_keys($node) !== range(0, count($node) - 1)){
                $infoNode = $this->analyseObject($node);
            }else{
                $infoNode = $this->analyseArray($node);
            }
        }else{
            $infoNode = $this->analyseNull($node);
        }

        return $infoNode;
        
    }

    private function analyseObject($node){
        $infoNode = array(
            "type" => "object",
            "required" => array(),
            "properties" => array()
        );

        foreach($node AS $key => $value){
            $infoNode['properties'][$key] = $this->analyseNode($value);
            $infoNode['required'][] = $key;
        }

        return $infoNode;
    }

    private function analyseArray($node){
        $infoNode = array(
            "type" => "array",
            "items" => $this->analyseNode($node[0])
        );

        return $infoNode;
    }

    private function analyseString($node){
        $infoNode = array(
            "type" => ["string","null"]
        );
        return $infoNode;
    }

    private function analyseNumeric($node){
        $infoNode = array(
            "type" => ["number","null"]
        );
        return $infoNode;
    }

    private function analyseBoolean($node){
        $infoNode = array(
            "type" => ["boolean","null"]
        );
        return $infoNode;
    }

    private function analyseNull($node){
        $infoNode = array(
            "type" => ["boolean","string","number","null"]
        );
        return $infoNode;
    }
}