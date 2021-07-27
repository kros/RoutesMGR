<?php
namespace Kros\RoutesMGR;

require "Controller.php";

use Kros\RoutesMGR\Controller;

class Route{
    private $method;
    private $path;
    private $controller;
    private $dataType;
    private $pathParams=array();
    private $pathParamsRegExp = '/{([a-zA-Z0-9_]*)}/i';
    private $pathParamRegExp = '([a-zA-Z0-9_]*)';
    private $pathRegExp;
    public function __construct($method, $path, $controller, $dataType='text/html'){
        $this->method = $method;
        $this->path = $path;
        $this->controller = new Controller($controller);
        $this->dataType = $dataType;
        // obtenemos los parámetros del path
        $count = preg_match_all($this->pathParamsRegExp, $this->path, $pathMatches);
        if ($count > 0){
            $this->pathParams = $pathMatches[1];
        }
        // calculamos la expresión regular del path
        // Si el path tiene el caracter '*' se hace que pueda admitir cualquier carcater no espacio
        if ($this->path){
            $pattern = preg_replace($this->pathParamsRegExp, $this->pathParamRegExp, str_replace('*', '\S*', $this->path));
            $this->pathRegExp = "/^".str_replace('/','\\/',$pattern)."\z/i";
        }
    }
    public function getMethod(){
        return $this->method;
    }
    public function setMethod($value){
        $this->method=$value;
    }
    public function getPath(){
        return $this->path;
    }
    public function setPath($value){
        $this->path=$value;
    }
    public function getController(){
        return $this->controller;
    }
    public function setController($value){
        $this->controller=$value;
    }
    public function getPathParams(){
        return $this->pathParams;
    }
    public function getDataType(){
        return $this->dataType;
    }
    public function setDataType($value){
        $this->dataType=$value;
    }
    public function extractPathParamsFromURI($uri){
        $res=array();

        if (count($this->pathParams)>0){
            $count=preg_match_all($this->pathRegExp, $uri, $matches);
            if (count($this->pathParams)==(count($matches)-1)){
                foreach ($this->pathParams as $ind=>$paramName){
                    $res[$paramName]=$matches[$ind+1][0];
                }
            }else{
                throw new \Exception('No se pueden extraer los parámetros de la URI');
            }
        
        }
        return $res;
    }    
    public function match($uri){
        $count=preg_match_all($this->pathRegExp, $uri, $matches);
        return $count>0;
    }
    public function getPathRegExp(){
        return $this->pathRegExp;
    }
}
?>
