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

    public function testClientWithKeyShouldReturnClient() {
        Tinify\setKey("abcde");
        $this->assertInstanceOf("Tinify\Client", Tinify\Tinify::getClient());
    }

    public function testClientWithoutKeyShouldThrowException() {
        $this->setExpectedException("Tinify\AccountException");
        $this->assertInstanceOf("Tinify\Client", Tinify\Tinify::getClient());
    }

    public function testValidateWithValidKeyShouldReturnTrue() {
        Tinify\setKey("valid");
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 400, "body" => '{"error":"InputMissing","message":"No input"}'
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
}
