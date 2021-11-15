<?php
namespace Kros\RoutesMGR;

require "Route.php";
require "Request.php";
//require "Controller.php";

use Exception;
use Kros\RoutesMGR\Route;
use Kros\RoutesMGR\Request;
//use Kros\RoutesMGR\Controller;

class RoutesManager{
    private $routes = array();
    private $defaultRoutes = array();
    private $defaultDataType = 'text/html';
    private $codeHandler = array();
    public function addRoute($method, $path, $controller, $dataType=null){
        $route = new Route(strtoupper($method), $path, $controller, $dataType==null?$this->defaultDataType:$dataType);
        $this->routes[]=$route;
        return $this;
    }
    public function addDefaultRoute($method, $path, $controller, $dataType=null){
        $route = new Route(strtoupper($method), $path, $controller, $dataType==null?$this->defaultDataType:$dataType);
        $this->defaultRoutes[$method]=$route;
        return $this;
    }
    public function getRoutes(){
        return $this->routes;
    }
    public function getDefaultRoutes(){
        return $this->defaultRoutes;
    }
    public function addController($controllerClass){
        try{
            $refClass = new \ReflectionClass($controllerClass); 
        }catch (\ReflectionException $e){
            return false;
        }
        $classDocComment = $refClass->getDocComment();

        $isDefaultController = strstr($classDocComment, '@defaultController');
        if (!$isDefaultController && !strstr($classDocComment, '@controller')){
            throw new \Exception("'$controllerClass' not annotated as controller");
        }

        $basePath = $this->getAnnotationMatch('path', $classDocComment);
        $basePath = trim($basePath?$basePath:'');

        foreach($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){
            $methodName = $method->getName();
            $refMethod = new \ReflectionMethod($controllerClass, $methodName);
            $methodDocComment = $refMethod->getDocComment();
            $methodPath = $this->getAnnotationMatch('path', $methodDocComment);
            $methodPath = trim($methodPath?$methodPath:'');
            $completePath = str_replace('//','/',$basePath.$methodPath);
            $methodType = $this->getAnnotationMatch('method', $methodDocComment);
            $dataType = $this->getAnnotationMatch('dataType', $methodDocComment);
            if ($methodType){
                $methodType=strtoupper($methodType);
                if ($isDefaultController){
                    $this->addDefaultRoute($methodType, $completePath, array($controllerClass, $methodName), $dataType?$dataType:$this->defaultDataType);
                }else{
                    $this->addRoute($methodType, $completePath, array($controllerClass, $methodName), $dataType?$dataType:$this->defaultDataType);
                }
            }
        }
        return true;
    }
    private function getAnnotationMatch($annotation, $searchString){
        $count = preg_match_all('/@'.$annotation.'\([ ]*([\/\{\}a-zA-Z0-9*_]+)[ ]*\)/', $searchString, $matches);
        if (count($matches)>1 && count($matches[1])>0){
            return $matches[1][0];
        }else{
            return null;
        }
    }
    public function importDir($dir, $filter='*.php'){
        $files = glob("$dir/$filter");
        if ($files){
            foreach($files as $file){
                $fileInfo = new \SplFileInfo($file);
                if ($fileInfo->isFile()){
                    include $file;
                    $extension=strstr($fileInfo->getFilename(),'.');
                    $className=str_replace($extension, '', $fileInfo->getFilename());
                    $this->addController($className);
                }
            }
        }
    }
    public function handle($uri){
        try{
            $req = new Request();
            $req->setUri($uri);
            $req->setRedirectURL($_SERVER['REDIRECT_URL']);
            //$req->setGetParams(parse_url($uri)['query']);
            $req->setGetParams($_GET);
            $req->setPostParams($_POST);
            $req->setMethod($_SERVER['REQUEST_METHOD']);
            $req->setBody(file_get_contents("php://input"));
            $req->setAccept($_SERVER['HTTP_ACCEPT']);
            if (array_key_exists('CONTENT_TYPE',$_SERVER)){
                $req->setContentType($_SERVER['CONTENT_TYPE']);
            }
            $response=null;
            foreach($this->routes as $route){
                if ($req->getMethod()==$route->getMethod() && $route->match($req->getRedirectURL())){
                    $controller = $route->getController();
                    $req->setPathParams($route->extractPathParamsFromURI($req->getRedirectURL()));
                    $this->process($route, $controller, $req, $response);

                    return $controller;
                }
            }
            // si llega aquí es que no había ningún controlador adecuado
            // así que se mira si hay ruta por defecto
            if ($this->defaultRoutes[$req->getMethod()]){
                $route = $this->defaultRoutes[$req->getMethod()];
                $controller = $route->getController();
                $req->setPathParams($route->extractPathParamsFromURI($uri));
                $this->process($route, $controller, $req, $response);

                return $controller;
            }
            http_response_code(404);
            throw new Exception("Controlador no encontrado para '$uri'");
        }catch (\Exception $e){
            if (array_key_exists(http_response_code(), $this->codeHandler)){
                $destination=$this->codeHandler[http_response_code()];
                header("Location: $destination");
            }
            throw $e;
        }
    }
    public function process($route, $controller, $req, $response){
        $pathParams=$req->getPathParams();
        $getParams=$req->getGetParams();
        $postParams=$req->getPostParams();
        $bodyParams=$req->getBodyParams();

        $params=array();
        if (count($controller->getControllerParams())>0){
            $controllerPathParamsMap=$controller->getPathParamsMap();
            $controllerGetParamsMap=$controller->getGetParamsMap();
            $controllerPostParamsMap=$controller->getPostParamsMap();
            $controllerRequestParamsMap=$controller->getRequestParamsMap();
            $controllerResponseParamsMap=$controller->getResponseParamsMap();
            $controllerBodyParamsMap=$controller->getBodyParamsMap();

            //inyeción de los distintos tipos de parámetros
            foreach($controller->getControllerParams() as $paramName){
                if (key_exists($paramName, $controllerPathParamsMap) && key_exists($controllerPathParamsMap[$paramName], $pathParams)){
                    $params += [$paramName=>$pathParams[$controllerPathParamsMap[$paramName]]];
                }else if (key_exists($paramName, $controllerGetParamsMap) && key_exists($controllerGetParamsMap[$paramName], $getParams)){
                    $params += [$paramName=>$getParams[$controllerGetParamsMap[$paramName]]];
                }else if (key_exists($paramName, $controllerPostParamsMap) && key_exists($controllerPostParamsMap[$paramName], $postParams)){
                    $params += [$paramName=>$postParams[$controllerPostParamsMap[$paramName]]];
                }else if (key_exists($paramName, $controllerBodyParamsMap) && key_exists($controllerBodyParamsMap[$paramName], $bodyParams)){
                    $params += [$paramName=>$bodyParams[$controllerBodyParamsMap[$paramName]]];
                }else if (key_exists($paramName, $controllerRequestParamsMap)){
                    $params += [$paramName=>$req];
                }else if (key_exists($paramName, $controllerResponseParamsMap)){
                    $params += [$paramName=>$response];
                }else if ($controller->isDefaultValueAvailable($paramName)){
                    $params += [$paramName=>$controller->getDefaultValue($paramName)];
                }
            }
        }
        ob_start();
        $res=$controller->invoke($params);
        $content = ob_get_clean();
        $returnContent=$res?$res:$content;
        if ($route->getDataType()=='text/html'){
            header('content-type: text/html; charset=utf-8');
            echo $content;
        }else if ($route->getDataType()=='application/json'){
            header('content-type: application/json; charset=utf-8');
            echo json_encode($returnContent);
        }
    }
    public function addCodeHandler($code, $destination){
        $this->codeHandler[$code]=$destination;
    }
}
?>
