<?php

require_once("curl_mock.php");
require_once("vendor/autoload.php");

class TestCase extends \PHPUnit_Framework_TestCase {
    function setUp() {
        Tinify\setKey(NULL);
        TInify\setProxy(NULL);
    }

    function tearDown() {
        Tinify\CurlMock::reset();
    }
}
