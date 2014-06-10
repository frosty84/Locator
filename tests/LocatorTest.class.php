<?

class LocatorTest extends PHPUnit_Framework_TestCase{
    protected $fx1arrange;
    protected $fx1test;
    protected $config;
    
    protected function setUp(){
        $this->fx1arrange = file_get_contents("./tests/fixtures/fx1arrange.txt");
        $this->fx1test = unserialize(file_get_contents("./tests/fixtures/fx1test.txt"));
        $this->config  = array( "key" => "testKey", 
                                "cacheDateFormat" => "YmdHis",
                                "cacheTimeSec" => 3600,
                                "curloptConnectTimeoutSec" => 30,
                                "googleTextSearch" => "https://test/textsearch");
        
    }

    /**
    * Test case: incorrect argument was passed to Locator's constructor
    * 
    */
    public function testRespTypeArgumentException(){
        
        //Set exception handler
        $this->setExpectedException('InvalidArgumentException', 'Wrong response type: test');
        
        //Act
        $locator = new Locator(array(), "test");
    }
    
    /**
    * Test case: empty query was passed
    * 
    */
    public function testCallNoQuery(){
        
        //Set exception handler
        $this->setExpectedException('InvalidArgumentException', "Empty request, can't proceed!");
        
        $request = $this->getMockBuilder('DispatchRequest')->disableOriginalConstructor()->getMock();
        //Act
        $locator = new Locator(array(), "xml");
        $locator->call($request);
        
    }
    
    /**
    * Test case: cache exists and valid, load from cache
    * 
    */
    public function testLoadFromCache(){
        
        //Arrange         
        $request = $this->getMockBuilder('DispatchRequest')->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getQuery')->will($this->returnValue('test'));
                        
        $mock = $this->getMock('Locator', array('isInCache', 'loadFromCache', 'isExpired'), array(array(), "xml"));
        
        //Assert
        $mock->expects($this->any())->method('isInCache')->will($this->returnValue("test"));
        $mock->expects($this->any())->method('isExpired')->will($this->returnValue(false));
        $mock->expects($this->once())->method('loadFromCache')->with("test");
        
        //Act
        $mock->call($request);
    }
    
    /**
    * Test case: there is no cache, call API
    * 
    */
    public function testCURLCall(){
        
        //Arrange         
        $request = $this->getMockBuilder('DispatchRequest')->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getQuery')->will($this->returnValue('test'));
        $chMock = $this->getMock('stdClass');
                        
        $mock = $this->getMock('Locator', array('isInCache', 'loadFromCache', 'isExpired', 'buildURL', 'getCURLObj', 'curlExec', 'putToCache', 'deleteCache'), array(array(), "xml"));
        
        //Assert
        $testUrl = "http://test";
        $testRes = "test";
        $mock->expects($this->any())->method('isInCache')->will($this->returnValue(false));
        $mock->expects($this->any())->method('isExpired')->will($this->returnValue(false));
        $mock->expects($this->once())->method('buildURL')->will($this->returnValue($testUrl))->with($request);
        $mock->expects($this->once())->method('getCURLObj')->will($this->returnValue($chMock))->with($testUrl);
        $mock->expects($this->once())->method('curlExec')->will($this->returnValue($testRes))->with($chMock);
        
        //Act
        $res = $mock->call($request);
        $this->assertEquals($testRes, $res);
    }
    
    /**
    * Test case: cache exists but expired
    * 
    */
    public function testCacheExpired(){
        $cache = "test.txt";
        $testRes = "test";
        $testQuery = "testQuery";
        
        //Arrange         
        $request = $this->getMockBuilder('DispatchRequest')->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getQuery')->will($this->returnValue($testQuery));
                        
        $mock = $this->getMock('Locator', array('isInCache', 'loadFromCache', 'isExpired', 'buildURL', 'getCURLObj', 'curlExec', 'putToCache', 'deleteCache'), array(array(), "xml"));
        
        //Assert
        $mock->expects($this->any())->method('isInCache')->will($this->returnValue($cache));
        $mock->expects($this->any())->method('isExpired')->will($this->returnValue(true));
        $mock->expects($this->once())->method('curlExec')->will($this->returnValue($testRes));
        #expect old cache file is deleted
        $mock->expects($this->once())->method('deleteCache')->with(Locator::CACHEDIR.$cache);
        #expect obtained data is loaded to cache
        $mock->expects($this->once())->method('putToCache')->with($testQuery, $testRes);
        
        //Act
        $res = $mock->call($request);
    }
    
    /**
    * Test case: inccoming XML to be processed
    * 
    */
    public function testXMLProcess(){
        $testQuery = "testQuery";
        
        //Arrange         
        $request = $this->getMockBuilder('DispatchRequest')->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getQuery')->will($this->returnValue($testQuery));
                        
        $mock = $this->getMock('Locator', array('isInCache', 'loadFromCache', 'isExpired', 'buildURL', 'getCURLObj', 'curlExec', 'putToCache', 'deleteCache'), array(array(), "xml"));
        
        //Act
        $response = $mock->process($this->fx1arrange);
        
        //Assert
        $this->assertEquals($response, $this->fx1test);
    }
    
    /**
    * Test case: there is no cache, call API
    * 
    */
    public function testBuildURL(){
        $url = "https://test/textsearch/xml?key=testKey&query=test&sensor=false";
        
        //Arrange         
        $request = $this->getMockBuilder('DispatchRequest')->disableOriginalConstructor()->getMock();
        $request->expects($this->any())->method('getQuery')->will($this->returnValue('test'));
        $chMock = $this->getMock('stdClass');
                        
        $mock = $this->getMock('Locator', array('isInCache', 'loadFromCache', 'isExpired', 'getCURLObj', 'curlExec', 'putToCache', 'deleteCache'), array($this->config, "xml"));
        
        //Assert
        $testUrl = "http://test";
        $testRes = "test";
        $mock->expects($this->any())->method('isInCache')->will($this->returnValue(false));
        $mock->expects($this->any())->method('isExpired')->will($this->returnValue(false));

        $mock->expects($this->once())->method('getCURLObj')->will($this->returnValue($chMock))->with($url);
        $mock->expects($this->once())->method('curlExec')->will($this->returnValue($testRes))->with($chMock);
        
        //Act
        $mock->call($request);
    }
}

?>