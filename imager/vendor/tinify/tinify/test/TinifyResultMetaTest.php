<?php

use Tinify\CurlMock;

class TinifyResultMetaTest extends TestCase {
    public function testWithMetadataWidthShouldReturnImageWidth() {
        $result = new Tinify\ResultMeta(array("image-width" => "100"));
        $this->assertSame(100, $result->width());
    }

    public function testWithMetadataHeightShouldReturnImageHeight() {
        $result = new Tinify\ResultMeta(array("image-height" => "60"));
        $this->assertSame(60, $result->height());
    }

    public function testWithMetadataLocationShouldReturnImageLocation() {
        $result = new Tinify\ResultMeta(array("location" => "https://example.com/image.png"));
        $this->assertSame("https://example.com/image.png", $result->location());
    }
}
