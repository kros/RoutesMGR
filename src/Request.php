<?php
namespace Kros\RoutesMGR;

use Kros\RoutesMGR\ContentType;

class Request{
    private $uri;
    private $getParams;
    private $postParams;
    private $pathParams;
    private $bodyParams;
    private $body;
    private $method;
    private $accept;
    private $redirectURL;
    private $contentType;
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
        $this->redirectURL=$value;
    }
    public function getResource(){
        $path = explode('/', $this->redirectURL);
        array_shift($path); // Hack; get rid of initials empty string
        return implode('/',$path);
    }
    public function setContentType($contentTypeString){
        $this->contentType = new ContentType($contentTypeString);
    }
    public function getContentType(){
        return $this->contentType;
    }
    public function getBodyParams(){
        $res=[];
        if ($this->body!=null && trim($this->body)!='' && $this->contentType!=null && $this->contentType->getMediaType()!=null){
            $mediaType = $this->contentType->getMediaType();
            if ($mediaType->getType()=='application'){
                switch ($mediaType->getSubtype()){
                    case 'json':
                        $res=json_decode($this->body,true);
                        break;
                    case 'x-www-form-urlencoded':
                        $aParams = explode("&", urldecode($this->body));
                        foreach($aParams as $param){
                            [$key,$value]=explode('=', $param);
                            $res[$key]=$value;
                        }
                        break;
                }
            }
        }
        return $res;
    }
}
?>
