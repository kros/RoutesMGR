<?php
namespace Kros\RoutesMGR;

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
        $count = preg_match_all('/@'.$annotation.'\([ ]*([a-zA-Z0-9_]+[ ]*(,[ ]*[a-zA-Z0-9_]+[ ]*)?)\)/', $this->getDocComment(), $matches);
        return $matches[1];
    }
    public function invoke($params=array()){
        try{
            if ($this->type=='FUNCTION'){
                return call_user_func_array($this->function, $params);
            }else{
                return call_user_func_array(array($this->class, $this->method), $params);
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
}
?>