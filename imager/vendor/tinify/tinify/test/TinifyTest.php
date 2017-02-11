<?php

use Tinify\CurlMock;

class ClientTest extends TestCase {
    private $dummyFile;

    public function setUp() {
        parent::setUp();
        $this->dummyFile = __DIR__ . "/examples/dummy.png";
    }

    public function testKeyShouldResetClientWithNewKey() {
        CurlMock::register("https://api.tinify.com/", array("status" => 200));
        Tinify\setKey("abcde");
        Tinify\Tinify::getClient();
        Tinify\setKey("fghij");
        $client = Tinify\Tinify::getClient();
        $client->request("get", "/");

        $this->assertSame("api:fghij", CurlMock::last(CURLOPT_USERPWD));
    }

    public function testAppIdentifierShouldResetClientWithNewAppIdentifier() {
        CurlMock::register("https://api.tinify.com/", array("status" => 200));
        Tinify\setKey("abcde");
        Tinify\setAppIdentifier("MyApp/1.0");
        Tinify\Tinify::getClient();
        Tinify\setAppIdentifier("MyApp/2.0");
        $client = Tinify\Tinify::getClient();
        $client->request("get", "/");

        $this->assertSame(Tinify\Client::userAgent() . " MyApp/2.0", CurlMock::last(CURLOPT_USERAGENT));
    }

    public function testProxyShouldResetClientWithNewProxy() {
        CurlMock::register("https://api.tinify.com/", array("status" => 200));
        Tinify\setKey("abcde");
        Tinify\setProxy("http://localhost");
        Tinify\Tinify::getClient();
        Tinify\setProxy("http://user:pass@localhost:8080");
        $client = Tinify\Tinify::getClient();
        $client->request("get", "/");

        $this->assertSame(Tinify\Client::userAgent() . " MyApp/2.0", CurlMock::last(CURLOPT_USERAGENT));
    }

    public function testClientWithKeyShouldReturnClient() {
        Tinify\setKey("abcde");
        $this->assertInstanceOf("Tinify\Client", Tinify\Tinify::getClient());
    }

    public function testClientWithoutKeyShouldThrowException() {
        $this->setExpectedException("Tinify\AccountException");
        Tinify\Tinify::getClient();
    }

    public function testClientWithInvalidProxyShouldThrowException() {
        $this->setExpectedException("Tinify\ConnectionException");
        Tinify\setKey("abcde");
        Tinify\setProxy("http-bad-url");
        Tinify\Tinify::getClient();
    }

    public function testSetClientShouldReplaceClient() {
        Tinify\setKey("abcde");
        Tinify\Tinify::setClient("foo");
        $this->assertSame("foo", Tinify\Tinify::getClient());
    }

    public function testValidateWithValidKeyShouldReturnTrue() {
        Tinify\setKey("valid");
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 400, "body" => '{"error":"Input missing","message":"No input"}'
        ));
        $this->assertTrue(Tinify\validate());
    }

    public function testValidateWithLimitedKeyShouldReturnTrue() {
        Tinify\setKey("invalid");
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 429, "body" => '{"error":"Too many requests","message":"Your monthly limit has been exceeded"}'
        ));
        $this->assertTrue(Tinify\validate());
    }

    public function testValidateWithErrorShouldThrowException() {
        Tinify\setKey("invalid");
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 401, "body" => '{"error":"Unauthorized","message":"Credentials are invalid"}'
        ));
        $this->setExpectedException("Tinify\AccountException");
        Tinify\validate();
    }

    public function testFromFileShouldReturnSource() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201, "headers" => array("Location" => "https://api.tinify.com/some/location")
        ));
        Tinify\setKey("valid");
        $this->assertInstanceOf("Tinify\Source", Tinify\fromFile($this->dummyFile));
    }

    public function testFromBufferShouldReturnSource() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201, "headers" => array("Location" => "https://api.tinify.com/some/location")
        ));
        Tinify\setKey("valid");
        $this->assertInstanceOf("Tinify\Source", Tinify\fromBuffer("png file"));
    }

    public function testFromUrlShouldReturnSource() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201, "headers" => array("Location" => "https://api.tinify.com/some/location")
        ));
        Tinify\setKey("valid");
        $this->assertInstanceOf("Tinify\Source", Tinify\fromUrl("http://example.com/test.jpg"));
    }
}
