<?php
namespace Kros\RoutesMGR;

class Request{
    private $uri;
    private $getParams;
    private $postParams;
    private $pathParams;
    private $body;
    private $method;
    private $accept;
    private $redirectURL;
    public function getUri(){
        return $this->uri;
    }
    public function setUri($value){
        $path = explode('/', $value);
        array_shift($path); // Hack; get rid of initials empty string
    
        $this->uri=implode('/', $path);
    }
    public function getGetParams(){
        return $this->getParams;
    }
    public function setGetParams($value){
        $this->getParams=$value;
    }
    public function getPostParams(){
        return $this->postParams;
    }
    public function setPostParams($value){
        $this->postParams=$value;
    }
    public function getPathParams(){
        return $this->pathParams;
    }
    public function setPathParams($value){
        $this->pathParams=$value;
    }
    public function getBody(){
        return $this->body;
    }
    public function setBody($value){
        $this->body=$value;
    }
    public function getMethod(){
        return $this->method;
    }
    public function setMethod($value){
        $this->method=$value;
    }
    public function getAccept(){
        return $this->accept;
    }
    public function setAccept($value){
        $this->accept=$value;
    }
    public function getRedirectURL(){
        return $this->redirectURL;
    }
    public function setRedirectURL($value){
        $path = explode('/', $value);
        array_shift($path); // Hack; get rid of initials empty string
    
        $this->redirectURL=implode('/', $path);
    }
}
?>
