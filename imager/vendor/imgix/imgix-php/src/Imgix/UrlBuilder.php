<?php

namespace Imgix;

class UrlBuilder {

    private $currentVersion = "1.1.0";
    private $domains;
    private $useHttps;
    private $signKey;
    private $shardStrategy;

    private $shardCycleNextIndex = 0;

    public function __construct($domains, $useHttps = false, $signKey = "", $shardStrategy = ShardStrategy::CRC, $includeLibraryParam = true) {
        if (!is_array($domains)) {
            $this->domains = array($domains);
        } else {
            $this->domains = $domains;
        }

        if (sizeof($this->domains) === 0) {
            throw new \InvalidArgumentException("UrlBuilder requires at least one domain");
        }

        $this->useHttps = $useHttps;
        $this->signKey = $signKey;
        $this->shardStrategy = $shardStrategy;
        $this->includeLibraryParam = $includeLibraryParam;
    }

    public function setShardStrategy($start) {
        $this->shardStrategy = $start;
    }

    public function setSignKey($key) {
        $this->signKey = $key;
    }

    public function setUseHttps($useHttps) {
        $this->useHttps = $useHttps;
    }

    public function createURL($path, $params=array()) {
        $scheme = $this->useHttps ? "https" : "http";

        if ($this->shardStrategy === ShardStrategy::CRC) {
            $index = self::unsigned_crc32($path) % sizeof($this->domains);
            $domain = $this->domains[$index];
        } else if ($this->shardStrategy === ShardStrategy::CYCLE) {
            $this->shardCycleNextIndex = ($this->shardCycleNextIndex + 1) % sizeof($this->domains);
            $domain = $this->domains[$this->shardCycleNextIndex];
        } else {
            $domain = $this->domains[0];
        }

        if ($this->includeLibraryParam) {
            $params['ixlib'] = "php-" . $this->currentVersion;
        }

        $uh = new UrlHelper($domain, $path, $scheme, $this->signKey, $params);

        return $uh->getURL();
    }

    // force unsigned int since 32-bit systems can return a signed integer
    // see warning here: http://php.net/manual/en/function.crc32.php
    public static function unsigned_crc32($v) {
        return intval(sprintf("%u", crc32($v)));
    }
}
