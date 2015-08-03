<?php

use Tinify\CurlMock;

class TinifyClientTest extends TestCase {
    public function testRequestWhenValidShouldIssueRequest() {
        CurlMock::register("https://api.tinify.com/", array("status" => 200));
        $client = new Tinify\Client("key");
        $client->request("get", "/");

        $this->assertSame("https://api.tinify.com/", CurlMock::last(CURLOPT_URL));
        $this->assertSame("api:key", CurlMock::last(CURLOPT_USERPWD));
    }

    public function testRequestWhenValidShouldIssueRequestWithJSONBody() {
        CurlMock::register("https://api.tinify.com/", array("status" => 200));
        $client = new Tinify\Client("key");
        $client->request("get", "/", array("hello" => "world"));

        $this->assertSame(array("Content-Type: application/json"), CurlMock::last(CURLOPT_HTTPHEADER));
        $this->assertSame('{"hello":"world"}', CurlMock::last(CURLOPT_POSTFIELDS));
    }

    public function testRequestWhenValidShouldIssueRequestWithUserAgent() {
        CurlMock::register("https://api.tinify.com/", array("status" => 200));
        $client = new Tinify\Client("key");
        $client->request("get", "/");

        $curl = curl_version();
        $this->assertSame(Tinify\Client::userAgent(), CurlMock::last(CURLOPT_USERAGENT));
    }

    public function testRequestWhenValidShouldUpdateCompressionCount() {
        CurlMock::register("https://api.tinify.com/", array(
            "status" => 200, "headers" => array("Compression-Count" => "12")
        ));
        $client = new Tinify\Client("key");
        $client->request("get", "/");

        $this->assertSame(12, Tinify\compressionCount());
    }

    public function testRequestWhenValidWithAppIdShouldIssueRequestWithUserAgent() {
        CurlMock::register("https://api.tinify.com/", array("status" => 200));
        $client = new Tinify\Client("key", "TestApp/0.1");
        $client->request("get", "/");

        $curl = curl_version();
        $this->assertSame(Tinify\Client::userAgent() . " TestApp/0.1", CurlMock::last(CURLOPT_USERAGENT));
    }

    public function testRequestWithUnexpectedErrorShouldThrowConnectionException() {
        CurlMock::register("https://api.tinify.com/", array(
            "error" => "Failed!", "errno" => 2
        ));
        $this->setExpectedException("Tinify\ConnectionException");
        $client = new Tinify\Client("key");
        $client->request("get", "/");
    }

    public function testRequestWithUnexpectedErrorShouldThrowExceptionWithMessage() {
        CurlMock::register("https://api.tinify.com/", array(
            "error" => "Failed!", "errno" => 2
        ));
        $this->setExpectedExceptionRegExp("Tinify\ConnectionException",
            "/Error while connecting: Failed! \(#2\)/");
        $client = new Tinify\Client("key");
        $client->request("get", "/");
    }

    public function testRequestWithServerErrorShouldThrowServerException() {
        CurlMock::register("https://api.tinify.com/", array(
            "status" => 584, "body" => '{"error":"InternalServerError","message":"Oops!"}'
        ));
        $this->setExpectedException("Tinify\ServerException");
        $client = new Tinify\Client("key");
        $client->request("get", "/");
    }

    public function testRequestWithServerErrorShouldThrowExceptionWithMessage() {
        CurlMock::register("https://api.tinify.com/", array(
            "status" => 584, "body" => '{"error":"InternalServerError","message":"Oops!"}'
        ));
        $this->setExpectedExceptionRegExp("Tinify\ServerException",
            "/Oops! \(HTTP 584\/InternalServerError\)/");
        $client = new Tinify\Client("key");
        $client->request("get", "/");
    }

    public function testRequestWithBadServerResponseShouldThrowServerException() {
        CurlMock::register("https://api.tinify.com/", array(
            "status" => 543, "body" => '<!-- this is not json -->'
        ));
        $this->setExpectedException("Tinify\ServerException");
        $client = new Tinify\Client("key");
        $client->request("get", "/");
    }

    public function testRequestWithBadServerResponseShouldThrowExceptionWithMessage() {
        CurlMock::register("https://api.tinify.com/", array(
            "status" => 543, "body" => '<!-- this is not json -->'
        ));
        if (PHP_VERSION_ID >= 50500) {
            $this->setExpectedExceptionRegExp("Tinify\ServerException",
                "/Error while parsing response: Syntax error \(#4\) \(HTTP 543\/ParseError\)/");
        } else {
            $this->setExpectedExceptionRegExp("Tinify\ServerException",
                "/Error while parsing response: Error \(#4\) \(HTTP 543\/ParseError\)/");
        }
        $client = new Tinify\Client("key");
        $client->request("get", "/");
    }

    public function testRequestWithClientErrorShouldThrowClientException() {
        CurlMock::register("https://api.tinify.com/", array(
            "status" => 492, "body" => '{"error":"BadRequest","message":"Oops!"}')
        );
        $this->setExpectedException("Tinify\ClientException");
        $client = new Tinify\Client("key");
        $client->request("get", "/");
    }

    public function testRequestWithClientErrorShouldThrowExceptionWithMessage() {
        CurlMock::register("https://api.tinify.com/", array(
            "status" => 492, "body" => '{"error":"BadRequest","message":"Oops!"}'
        ));
        $this->setExpectedExceptionRegExp("Tinify\ClientException",
            "/Oops! \(HTTP 492\/BadRequest\)/");
        $client = new Tinify\Client("key");
        $client->request("get", "/");
    }

    public function testRequestWithBadCredentialsShouldThrowAccountException() {
        CurlMock::register("https://api.tinify.com/", array(
            "status" => 401, "body" => '{"error":"Unauthorized","message":"Oops!"}'
        ));
        $this->setExpectedException("Tinify\AccountException");
        $client = new Tinify\Client("key");
        $client->request("get", "/");
    }

    public function testRequestWithBadCredentialsShouldThrowExceptionWithMessage() {
        CurlMock::register("https://api.tinify.com/", array(
            "status" => 401, "body" => '{"error":"Unauthorized","message":"Oops!"}'
        ));
        $this->setExpectedExceptionRegExp("Tinify\AccountException",
            "/Oops! \(HTTP 401\/Unauthorized\)/");
        $client = new Tinify\Client("key");
        $client->request("get", "/");
    }
}
