<?php

namespace Tinify;

class CurlMockException extends Exception {
}

class CurlMock {
    private static $urls = array();
    private static $requests = array();

    public $options = array();
    public $response;
    public $closed = false;

    public static function register($url, $request, $response = NULL) {
        if (!$response) {
            $response = $request;
            $request = NULL;
        }
        self::$urls[$url] = array($request, $response);
    }

    public static function reset() {
        self::$requests = array();
    }

    public static function last_has($key) {
        $lastReq = self::$requests[count(self::$requests) - 1];
        return array_key_exists($key, $lastReq->options);
    }

    public static function last($key = null) {
        $lastReq = self::$requests[count(self::$requests) - 1];
        if ($key) {
            return $lastReq->options[$key];
        } else {
            return $lastReq;
        }
    }

    public function close() {
        $this->closed = true;
    }

    public function exec() {
        if ($this->closed) {
            throw new CurlMockException("Curl already closed");
        }
        array_push(self::$requests, $this);

        list($this->request, $this->response) = self::$urls[$this->options[CURLOPT_URL]];
        if ($this->request) {
            if ($this->request["body"]) {
                if ($this->options[CURLOPT_POSTFIELDS] != $this->request["body"]) {
                    throw new Exception("Body '" . $this->options[CURLOPT_POSTFIELDS] .
                        "' does not match expected '" . $this->request["body"] . "'");
                }
            }
        }

        if (isset($this->response["headers"])) {
            $headers = "";
            foreach ($this->response["headers"] as $header => $value) {
                $headers .= $header . ": " . $value . "\r\n";
            }
            $this->response["headers"] = $headers . "\r\n";
        } else {
            $this->response["headers"] = "\r\n";
        }

        if (!isset($this->response["body"])) {
            $this->response["body"] = "";
        }

        if (array_key_exists("return", $this->response)) {
            return $this->response["return"];
        } else if (isset($this->response["status"])) {
            return $this->response["headers"] . $this->response["body"];
        } else {
            return false;
        }
    }

    public function setopt_array($array) {
        if ($this->closed) {
            throw new CurlMockException("Curl already closed");
        }
        foreach ($array as $key => $value) {
            $this->options[$key] = $value;
        }
    }

    public function setopt($key, $value) {
        if ($this->closed) {
            throw new CurlMockException("Curl already closed");
        }
        $this->options[$key] = $value;
    }

    public function getinfo($key) {
        if ($this->closed) {
            throw new CurlMockException("Curl already closed");
        }
        switch ($key) {
            case CURLINFO_HTTP_CODE:
                return isset($this->response["status"]) ? $this->response["status"] : 0;
            case CURLINFO_HEADER_SIZE:
                return strlen($this->response["headers"]);
            default:
                throw new Exception("Bad key $key");
        }
    }

    public function error() {
        if ($this->closed) {
            throw new CurlMockException("Curl already closed");
        }
        return $this->response["error"];
    }

    public function errno() {
        if ($this->closed) {
            throw new CurlMockException("Curl already closed");
        }
        return $this->response["errno"];
    }
}

function curl_init() {
    return new CurlMock();
}

function curl_exec($mock) {
    return $mock->exec();
}

function curl_close($mock) {
    $mock->close();
}

function curl_setopt_array($mock, $array) {
    return $mock->setopt_array($array);
}

function curl_setopt($mock, $key, $value) {
    return $mock->setopt($key, $value);
}

function curl_getinfo($mock, $key) {
    return $mock->getinfo($key);
}

function curl_error($mock) {
    return $mock->error();
}

function curl_errno($mock) {
    return $mock->errno();
}
