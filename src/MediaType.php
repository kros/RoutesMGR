<?php
namespace Kros\RoutesMGR;

class MediaType{
    private $type;
    private $subtype;
    private $mimetype;
    public function __construct($mediaTypeString){
        $this->mimetype=$mediaTypeString;
        [$this->type,$this->subtype]=explode('/', $mediaTypeString);
    }
    public function getType(){
        return $this->type;
    }
    public function getSubtype(){
        return $this->subtype;
    }
    public function getMIMEType(){
        return $this->mimetype;
    }
}