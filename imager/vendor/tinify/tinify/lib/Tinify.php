<?php

namespace Tinify;

const VERSION = "0.9.1";

class Tinify {
    public static $key = NULL;
    public static $appIdentifier = NULL;
    public static $compressionCount = NULL;

    public static $client = NULL;

    public static function getClient() {
        if (!self::$key) {
            throw new AccountException("Provide an API key with Tinify.key = ...");
        }

        if (!self::$client) {
            self::$client = new Client(self::$key);
        }

        return self::$client;
    }
}

function setKey($key) {
    Tinify::$key = $key;
    Tinify::$client = NULL;
}

function setAppIdentifier($appIdentifier) {
    Tinify::$appIdentifier = $appIdentifier;
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

function compressionCount() {
    return Tinify::$compressionCount;
}
