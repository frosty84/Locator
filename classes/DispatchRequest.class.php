<?php
  /**
  * DispatchRequest
  * 
  * Simple class to contain and return search query
  * 
  * @author Vitalii Bondarenko 
  * @version  0.2
  */
  
  class DispatchRequest implements iRequest{
      protected $query;
      
      /**
      * DispatchRequest constructor
      * 
      * @param array $get
      * @return DispatchRequest
      */
      public function __construct($get){
          if(!($this->query = urlencode($get['query'])))
            throw new Exception("Empty query");
      }
      
      /**
      * Returns search query
      * @return string
      */
      public function getQuery(){
          return $this->query;
      }
  }
