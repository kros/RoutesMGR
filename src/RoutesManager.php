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
    private $defaultContentType = 'text/html';
    private $codeHandler = array();
    public function addRoute($method, $path, $controller, $contentType=null){
        $route = new Route(strtoupper($method), $path, $controller, $contentType==null?$this->defaultContentType:$contentType);
        $this->routes[]=$route;
        return $this;
    }
    public function getRoutes(){
        return $this->routes;
    }
    public function addController($controllerClass){
        try{
            $refClass = new \ReflectionClass($controllerClass); 
        }catch (\ReflectionException $e){
            return false;
        }
        $classDocComment = $refClass->getDocComment();
        if (!strstr($classDocComment, '@controller')){
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
            $contentType = $this->getAnnotationMatch('contentType', $methodDocComment);
            if ($methodType){
                $methodType=strtoupper($methodType);
                $this->addRoute($methodType, $completePath, array($controllerClass, $methodName), $contentType?$contentType:$this->defaultContentType);
            }
        }
        return true;
    }
    private function getAnnotationMatch($annotation, $searchString){
        $count = preg_match_all('/@'.$annotation.'\([ ]*([\/\{\}a-zA-Z0-9_]+)[ ]*\)/', $searchString, $matches);
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
            $response=null;
            foreach($this->routes as $route){
                if ($req->getMethod()==$route->getMethod() && $route->match($uri)){
                    $controller = $route->getController();
                    $req->setPathParams($route->extractPathParamsFromURI($uri));

                    $pathParams=$req->getPathParams();
                    $getParams=$req->getGetParams();
                    $postParams=$req->getPostParams();

                    $params=array();
                    if (count($controller->getControllerParams())>0){
                        $controllerPathParamsMap=$controller->getPathParamsMap();
                        $controllerGetParamsMap=$controller->getGetParamsMap();
                        $controllerPostParamsMap=$controller->getPostParamsMap();
                        $controllerRequestParamsMap=$controller->getRequestParamsMap();
                        $controllerResponseParamsMap=$controller->getResponseParamsMap();
    
                        //inyeción de los distintos tipos de parámetros
                        foreach($controller->getControllerParams() as $paramName){
                            if (key_exists($paramName, $controllerPathParamsMap) && key_exists($controllerPathParamsMap[$paramName], $pathParams)){
                                $params += [$paramName=>$pathParams[$controllerPathParamsMap[$paramName]]];
                            }else if (key_exists($paramName, $controllerGetParamsMap) && key_exists($controllerGetParamsMap[$paramName], $getParams)){
                                $params += [$paramName=>$getParams[$controllerGetParamsMap[$paramName]]];
                            }else if (key_exists($paramName, $controllerPostParamsMap) && key_exists($controllerPostParamsMap[$paramName], $postParams)){
                                $params += [$paramName=>$postParams[$controllerPostParamsMap[$paramName]]];
                            }else if (key_exists($paramName, $controllerRequestParamsMap)){
                                $params += [$paramName=>$req];
                            }else if (key_exists($paramName, $controllerResponseParamsMap)){
                                $params += [$paramName=>$response];
                            }
                        }
                    }
                    ob_start();
                    $res=$controller->invoke($params);
                    $content = ob_get_clean();
                    $returnContent=$res?$res:$content;
                    if ($route->getContentType()=='text/html'){
                        header('content-type: text/html; charset=utf-8');
                        echo $content;
                    }else if ($route->getContentType()=='application/json'){
                        header('content-type: application/json; charset=utf-8');
                        echo json_encode($returnContent);
                    }

                    return $controller;
                }
            }
            http_response_code(404);
            throw new Exception("Controlador no encontrado");
        }catch (\Exception $e){
            if (http_response_code()==200){
                http_response_code(500);
            }
            echo "Error: ".$e->getMessage();
            if ($destination=$this->codeHandler[http_response_code()]){
                header("Location: $destination");
            }
        }
    }
    public function addCodeHandler($code, $destination){
        $this->codeHandler[$code]=$destination;
    }
}
?>
