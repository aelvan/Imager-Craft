<?php

namespace Tinify;

const VERSION = "1.1.1";

class Tinify {
    private static $key = NULL;
    private static $appIdentifier = NULL;
    private static $compressionCount = NULL;
    private static $client = NULL;

    public static function setKey($key) {
        self::$key = $key;
        self::$client = NULL;
    }

    public static function setAppIdentifier($appIdentifier) {
        self::$appIdentifier = $appIdentifier;
        self::$client = NULL;
    }

    public static function getCompressionCount() {
        return self::$compressionCount;
    }

    public static function setCompressionCount($compressionCount) {
        self::$compressionCount = $compressionCount;
    }

    public static function getClient() {
        if (!self::$key) {
            throw new AccountException("Provide an API key with Tinify\setKey(...)");
        }

        if (!self::$client) {
            self::$client = new Client(self::$key, self::$appIdentifier);
        }

        return self::$client;
    }
}

function setKey($key) {
    return Tinify::setKey($key);
}

function setAppIdentifier($appIdentifier) {
    return Tinify::setAppIdentifier($appIdentifier);
}

function getCompressionCount() {
    return Tinify::getCompressionCount();
}

function compressionCount() {
    return Tinify::getCompressionCount();
}

function fromFile($path) {
    return Source::fromFile($path);
}

function fromBuffer($string) {
    return Source::fromBuffer($string);
}

function validate() {
    try {
        Tinify::getClient()->request("post", "/shrink");
    } catch (ClientException $e) {
        return true;
    }
}
