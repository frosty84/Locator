<?php

  /**
  * Locator
  * 
  * Class to make a call to Google Places API
  * And process response
  * 
  * @author Vitalii Bondarenko 
  * @version  0.2
  */

  class Locator{
      protected $key;
      protected $cacheDateFormat;
      protected $cacheTimeSec;
      protected $curloptReferer;
      protected $curloptConnectTimeoutSec;
      protected $googleTextSearch;
      protected $request;
      protected $respType;
      
      protected static $RESPTYPES = array('xml', 'json');
      const CACHEDIR = "./cache/";
      
      /**
      * Locator's constructor
      * 
      * @param array $config configuration options
      * @param string $respType type of requested data: xml|json
      * @return Locator
      */
      public function __construct($config, $respType){
          foreach($config as $k=>$v){
            $this->$k = $v;                         
          }
          if(in_array($respType, self::$RESPTYPES))
            $this->respType = $respType;
          else 
            throw new InvalidArgumentException("Wrong response type: ".$respType);
      }
      
      /**
      * Calls google api using query from $request
      * 
      * @param iRequest $request object that generates search query
      * @return string obtained response
      */
      public function call(iRequest $request){
          $res = "";
          if(!$request->getQuery())
            throw new InvalidArgumentException("Empty request, can't proceed!");
            
          if(!($cache = $this->isInCache($request->getQuery())) || $this->isExpired($cache)){
              $url  = $this->buildURL($request);
              $ch   = $this->getCURLObj($url);
              $res  = $this->curlExec($ch);
              
              $this->deleteCache(self::CACHEDIR.$cache);
              $this->putToCache($request->getQuery(), $res);
          }
          else
            $res = $this->loadFromCache($cache);
          return $res;
      }
      
      /**
      * Parses incoming XML,
      * Looks for establishment Name/Address pairs
      * Return associative array, where key is a name
      * And address is a value
      * 
      * @param string $xml
      * @return array
      */
      public function process($xml){
          $result = array();
          if($xml){
              $doc = $this->loadXML($xml);  
              $xpath = new DOMXpath($doc);
              $results = $xpath->query("/PlaceSearchResponse/status");
              if(strtolower($results->item(0)->nodeValue) == "ok"){
                  $elements = $xpath->query("/PlaceSearchResponse/result");
                  if (!is_null($elements)) {
                      foreach ($elements as $element) {
                          $name = $xpath->query('name', $element)->item(0)->nodeValue;
                          $addr = $xpath->query('formatted_address', $element)->item(0)->nodeValue;
                          if($name && $addr){
                              $result[$name] = $addr;
                          }
                      }
                  }
              }  
          }
          return $result;          
      }
      
      /**
      * Delete cache file
      * 
      * @param mixed $file
      */
      protected function deleteCache($file){
          if(is_file($file))
            unlink($file);
      }
      
      /**
      * Execure cURL call
      * 
      * @param resource $ch
      * @return string obtained response
      * 
      */
      protected function curlExec($ch){
          $res = curl_exec($ch);
          $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          if($res === false)
              throw new Exception(sprintf('Curl error: %s', curl_error($ch)));
          if(preg_match("/^[45]\d{2}/", $status)){
              throw new Exception(sprintf('Connection issue: HTTP %d', $status));
          }
          curl_close ($ch);
          return $res;
      }
      
      /**
      * Returns cURL object
      * 
      * @param string $url google's API URL
      * @return resource
      */
      protected function getCURLObj($url){
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->curloptConnectTimeoutSec);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
          curl_setopt($ch, CURLOPT_REFERER, $this->curloptReferer);
          return $ch;
      }
      
      /**
      * Loads data from cache
      * 
      * @param string $cache file name
      * @return string
      */
      protected function loadFromCache($cache){
          $data = "";
          $f = fopen(self::CACHEDIR.$cache,'r'); 
          if($fsize = filesize(self::CACHEDIR.$cache))
            $data = fread($f, $fsize); 
          fclose($f);
          return $data;  
      }
      
      /**
      * Checks if cache is expired
      * 
      * @param string $cache file name
      * @return bool
      */
      protected function isExpired($cache){ 
          $matches = array();
          preg_match('/_(\d+)\.txt$/', $cache, $matches);
          $cdate = DateTime::createFromFormat($this->cacheDateFormat, $matches[1]);
          $diff = time() - $cdate->getTimestamp(); 
          return $diff >= $this->cacheTimeSec;
      }
      
      /**
      * Check if data for particular request is already in cache
      * 
      * @param string $query search query
      * @return bool|string false if there is no cache, otherwise filename
      */
      protected function isInCache($query){ 
          $isInCache = false;
          if (!is_writable(self::CACHEDIR)) 
            throw new Exception("Cache directory is not writable");
                                  
          $d = @dir(self::CACHEDIR);
          if(!$d)
              throw new Exception("Can't open cache directory for reading");   
          
          while(false !== ($entry = $d->read())) {
              if(preg_match("/^[\.]+$/", $entry)) continue;
              $fdata = preg_split("/_/", $entry);
              if($fdata[0] == md5($query)){
                  $isInCache = $entry;        
              }
          }
          return $isInCache;
      }
      
      /**
      * Puts some data into cache
      * 
      * @param string $query search query
      * @param string $xml data to be cached
      */
      protected function putToCache($query, $xml){
          if($xml){
              $fname = sprintf("%s_%s.txt", md5($query), date($this->cacheDateFormat));
              $fp = fopen(self::CACHEDIR.$fname, 'w');
              fwrite($fp, $xml);
              fclose($fp);
          }
      }
      
      /**
      * Creates DOMDocument from plain input XML string
      * 
      * @param string $xml
      * @return DOMDocument
      */
      protected function loadXML($xml){
          set_error_handler('HandleXmlError');
          $doc = new DOMDocument();
          $doc->loadXML($xml);
          restore_error_handler();
          return $doc;
      }
      
      /**
      * Builds google API URL
      * 
      * @param iRequest $request
      */
      protected function buildURL(iRequest $request){
          $url = sprintf("%s/%s?", $this->googleTextSearch, $this->respType);
          $params = array(  "key"       => $this->key,
                            "query"     => $request->getQuery(),
                            "sensor"    => "false");
          foreach($params as $k=>$v){
            $url.=sprintf("&%s=%s", $k, $v);
          }
          return str_replace("?&", "?", $url);
      }
  }