<?php
namespace Kros\RoutesMGR;

use ReflectionClass;

class Controller{
    private $type;
    private $function;
    private $class;
    private $method;
    private $controllerParams=array();
    private $pathParamsMap=array();
    private $getParamsMap=array();
    private $postParamsMap=array();
    private $requestParamsMap=array();
    private $responseParamsMap=array();
    private $bodyParamsMap=array();
    /**
     * Constructor de la clase
     * @param mixed $controller Controlador: puede ser un string con el nombre de una función o un array con 2 elemenos (nombre de la clase, método)
     */
    public function __construct($controller){
        if (is_array($controller) && count($controller)==2){
            $this->type='METHOD';
            $this->class=$controller[0];
            $this->method=$controller[1];
        }elseif (is_string($controller)){
            $this->type='FUNCTION';
            $this->function=$controller;
        }else{
            throw new \Exception('Invalid controller');
        }
        foreach($this->getParameters() as $param){
            $this->controllerParams[] = $param->getName();
        }
        //$this->configurePathParamsMap();
        $this->pathParamsMap = $this->configureParamsMap('pathParam');
        $this->getParamsMap = $this->configureParamsMap('getParam');
        $this->postParamsMap = $this->configureParamsMap('postParam');
        $this->requestParamsMap = $this->configureParamsMap('requestParam');
        $this->responseParamsMap = $this->configureParamsMap('responseParam');
        $this->bodyParamsMap = $this->configureParamsMap('bodyParam');
    }
    public function getControllerParams(){
        return $this->controllerParams;
    }
    public function getPathParamsMap(){
        return $this->pathParamsMap;
    }
    public function getGetParamsMap(){
        return $this->getParamsMap;
    }
    public function getPostParamsMap(){
        return $this->postParamsMap;
    }
    public function getRequestParamsMap(){
        return $this->requestParamsMap;
    }
    public function getResponseParamsMap(){
        return $this->responseParamsMap;
    }
    public function getBodyParamsMap(){
        return $this->bodyParamsMap;
    }
    private function configureParamsMap($annotation){
        $res=array();
        $matches = $this->getAnnotationMatches($annotation);
        foreach($matches as $match){
            $map = explode(',', trim($match));
            if (count($map)==1){
                $map[]=trim($match);
            }
            if (in_array(trim($map[0]), $this->controllerParams)){
                $res += [trim($map[0])=>trim($map[1])];
            }else{
                throw new \Exception("Defined $annotation(".trim($map[0]).') not found in controller params.');
            }
        }
        return $res;
    }
    private function getAnnotationMatches($annotation){
        $count = preg_match_all('/@'.$annotation.'\([ ]*([a-zA-Z0-9_ñÑ]+[ ]*(,[ ]*[a-zA-Z0-9_ñÑ]+[ ]*)?)\)/', $this->getDocComment(), $matches);
        return $matches[1];
    }
    public function invoke($params=array()){
        try{
            if ($this->type=='FUNCTION'){
                return call_user_func_array($this->function, $params);
            }else{
                $class = new ReflectionClass($this->class);
                $method = $class->getMethod($this->method);
                if ($method->isStatic()){
                    $object = null;
                }else{
                    $object = $class->newInstance();
                }
                return $method->invokeArgs($object,$params);
            }
        }catch(\Error $e){
            throw new \Exception($e->getMessage());
        }
    }
    private function getReflection(){
        if ($this->type=='METHOD'){
            $ref = new \ReflectionMethod($this->class, $this->method);
        }elseif ($this->type=='FUNCTION'){
            $ref = new \ReflectionFunction($this->function);
        }
        return $ref;
    }
    public function getParameters(){
        return $this->getReflection()->getParameters();        
    }
    public function getDocComment(){
        return $this->getReflection()->getDocComment();
    }
    public function toString(){
        return ($this->type.": ".($this->type=='FUNCTION'?$this->function:$this->class.'::'.$this->method));
    }
    public function getParameter($paramName){
        foreach($this->getParameters() as $param){
            if ($param->getName()==$paramName){
                return $param;
            }
        }
        throw new \Exception("Parameter '$paramName' doesn't exists in controller method/function");
    }
    public function isDefaultValueAvailable($paramName){
        return $this->getParameter($paramName)->isDefaultValueAvailable();
    }
    public function getDefaultValue($paramName){
        return $this->getParameter($paramName)->getDefaultValue();
    }
}
?>
