<?php
namespace Kros\RoutesMGR;

class ContentType{
    private $mediaType;
    private $charset;
    private $boundary;

    public function __construct($contentTypeString)
    {
        $parts = explode(';', $contentTypeString);
        foreach($parts as $part){
            $pair = explode('=', $part);
            if (sizeof($pair)==1){
                $this->mediaType=new MediaType(trim($pair[0]));
            }else if (sizeof($pair)==2){
                switch (trim($pair[0])){
                    case 'charset':
                        $this->charset=trim($pair[1]);
                        break;
                    case 'boundary':
                        $this->boundary=trim($pair[1]);
                        break;
                    default:
                    break;
                }
            }
        }
    }
    public function getMediaType(){
        return $this->mediaType;
    }
    public function getCharset(){
        return $this->charset;
    }
    public function getBoundary(){
        return $this->boundary;
    }
    public function getMIMEType(){
        return $this->mediaType->getMIMEType();
    }
}