<?php

use Tinify\CurlMock;

class TinifySourceTest extends TestCase {
    private $dummyFile;

    public function setUp() {
        parent::setUp();
        $this->dummyFile = __DIR__ . "/examples/dummy.png";
    }

    public function testFromFileWithInvalidApiKeyShouldThrowAccountException() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 401, "body" => '{"error":"Unauthorized","message":"Oops!"}'
        ));
        Tinify\setKey("invalid");

        $this->setExpectedException("Tinify\AccountException");
        Tinify\Source::fromFile($this->dummyFile);
    }

    public function testFromBufferWithInvalidApiKeyShouldThrowAccountException() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 401, "body" => '{"error":"Unauthorized","message":"Oops!"}'
        ));
        Tinify\setKey("invalid");

        $this->setExpectedException("Tinify\AccountException");
        Tinify\Source::fromBuffer("png file");
    }

    public function testFromFileShouldReturnSource() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201, "headers" => array("Location" => "https://api.tinify.com/some/location")
        ));
        Tinify\setKey("valid");
        $this->assertInstanceOf("Tinify\Source", Tinify\Source::fromFile($this->dummyFile));
    }

    public function testFromFileShouldReturnSourceWithData() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201, "headers" => array("Location" => "https://api.tinify.com/some/location")
        ));
        CurlMock::register("https://api.tinify.com/some/location", array(
            "status" => 200, "body" => "compressed file"
        ));
        Tinify\setKey("valid");
        $this->assertSame("compressed file", Tinify\Source::fromFile($this->dummyFile)->toBuffer());
    }

    public function testFromBufferShouldReturnSource() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201, "headers" => array("Location" => "https://api.tinify.com/some/location")
        ));
        Tinify\setKey("valid");
        $this->assertInstanceOf("Tinify\Source", Tinify\Source::fromBuffer("png file"));
    }

    public function testFromBufferShouldReturnSourceWithData() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201, "headers" => array("Location" => "https://api.tinify.com/some/location")
        ));
        CurlMock::register("https://api.tinify.com/some/location", array(
            "status" => 200, "body" => "compressed file"
        ));
        Tinify\setKey("valid");
        $this->assertSame("compressed file", Tinify\Source::fromBuffer("png file")->toBuffer());
    }

    public function testResultShouldReturnResult() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201,
            "headers" => array("Location" => "https://api.tinify.com/some/location"),
        ));

        Tinify\setKey("valid");
        $this->assertInstanceOf("Tinify\Result", Tinify\Source::fromBuffer("png file")->result());
    }

    public function testResizeShouldReturnSource() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201, "headers" => array("Location" => "https://api.tinify.com/some/location")
        ));
        CurlMock::register("https://api.tinify.com/some/location", array(
            "status" => 200, "body" => "small file"
        ));
        Tinify\setKey("valid");
        $this->assertInstanceOf("Tinify\Source", Tinify\Source::fromBuffer("png file")->resize(array("width" => 400)));
    }

    public function testResizeShouldReturnSourceWithData() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201, "headers" => array("Location" => "https://api.tinify.com/some/location")
        ));
        CurlMock::register("https://api.tinify.com/some/location", array(
            "status" => 200, "body" => "small file"
        ));
        Tinify\setKey("valid");
        $this->assertSame("small file", Tinify\Source::fromBuffer("png file")->resize(array("width" => 400))->toBuffer());
    }

    public function testStoreShouldReturnResult() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201,
            "headers" => array("Location" => "https://api.tinify.com/some/location"),
        ));

        CurlMock::register("https://api.tinify.com/some/location", array(
            "body" => '{"store":{"service":"s3","aws_secret_access_key":"abcde"}}'
        ), array("status" => 200));

        Tinify\setKey("valid");
        $options = array("service" => "s3", "aws_secret_access_key" => "abcde");
        $this->assertInstanceOf("Tinify\Result", Tinify\Source::fromBuffer("png file")->store($options));
    }

    public function testStoreShouldMergeCommands() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201,
            "headers" => array("Location" => "https://api.tinify.com/some/location"),
        ));

        CurlMock::register("https://api.tinify.com/some/location", array(
            "body" => '{"resize":{"width":300},"store":{"service":"s3","aws_secret_access_key":"abcde"}}'
        ), array("status" => 200));

        Tinify\setKey("valid");
        $options = array("service" => "s3", "aws_secret_access_key" => "abcde");
        $this->assertInstanceOf("Tinify\Result", Tinify\Source::fromBuffer("png file")->resize(array("width" => 300))->store($options));
    }

    public function testToBufferShouldReturnImageData() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201, "headers" => array("Location" => "https://api.tinify.com/some/location")
        ));
        CurlMock::register("https://api.tinify.com/some/location", array(
            "status" => 200, "body" => "compressed file"
        ));

        Tinify\setKey("valid");
        $this->assertSame("compressed file", Tinify\Source::fromBuffer("png file")->toBuffer());
    }

    public function testToFileShouldStoreImageData() {
        CurlMock::register("https://api.tinify.com/shrink", array(
            "status" => 201, "headers" => array("Location" => "https://api.tinify.com/some/location")
        ));
        CurlMock::register("https://api.tinify.com/some/location", array(
            "status" => 200, "body" => "compressed file"
        ));

        $path = tempnam(sys_get_temp_dir(), "tinify-php");
        Tinify\setKey("valid");
        Tinify\Source::fromBuffer("png file")->toFile($path);
        $this->assertSame("compressed file", file_get_contents($path));
    }
}
