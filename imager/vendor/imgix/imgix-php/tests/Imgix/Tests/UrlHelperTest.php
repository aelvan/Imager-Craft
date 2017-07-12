<?php

use Imgix\UrlHelper;

class UrlHelperTest extends PHPUnit_Framework_TestCase {

    public function testHelperBuildSignedURLWithHashMapParams() {
        $params = array("w" => 500);
        $uh = new URLHelper("imgix-library-secure-test-source.imgix.net", "dog.jpg", "http", "EHFQXiZhxP4wA2c4", $params);

        $this->assertEquals("http://imgix-library-secure-test-source.imgix.net/dog.jpg?w=500&s=e4eb402d12bbdf267bf0fc5588170d56", $uh->getURL());
    }

    public function testHelperBuildSignedURLWithHashMapAndNoParams() {
        $params = array();
        $uh = new URLHelper("imgix-library-secure-test-source.imgix.net", "dog.jpg", "http", "EHFQXiZhxP4wA2c4", $params);

        $this->assertEquals("http://imgix-library-secure-test-source.imgix.net/dog.jpg?s=2b0bc99b1042e3c1c9aae6598acc3def", $uh->getURL());
    }

    public function testHelperBuildSignedURLWithHashSetterParams() {
        $uh = new URLHelper("imgix-library-secure-test-source.imgix.net", "dog.jpg", "http", "EHFQXiZhxP4wA2c4");
        $uh->setParameter("w", 500);
        $this->assertEquals("http://imgix-library-secure-test-source.imgix.net/dog.jpg?w=500&s=e4eb402d12bbdf267bf0fc5588170d56", $uh->getURL());
    }

    public function testHelperBuildSignedURLWithHashSetterParamsHttps() {
        $uh = new URLHelper("imgix-library-secure-test-source.imgix.net", "dog.jpg", "https", "EHFQXiZhxP4wA2c4");
        $uh->setParameter("w", 500);
        $this->assertEquals("https://imgix-library-secure-test-source.imgix.net/dog.jpg?w=500&s=e4eb402d12bbdf267bf0fc5588170d56", $uh->getURL());
    }
}

?>
