<?php

namespace aelvan\imager\models;

interface TransformedImageInterface
{
    /**
     * @return string
     */
    public function getPath():string;

    /**
     * @return string
     */
    public function getFilename():string;

    /**
     * @return string
     */
    public function getUrl():string;

    /**
     * @return string
     */
    public function getExtension():string;

    /**
     * @return string
     */
    public function getMimeType():string;

    /**
     * @return int
     */
    public function getWidth():int;

    /**
     * @return int
     */
    public function getHeight():int;

    /**
     * @param string $unit
     * @param int $precision
     * @return mixed
     */
    public function getSize($unit = 'b', $precision = 2);

    /**
     * @return string
     */
    public function getDataUri():string;

    /**
     * @return string
     */
    public function getBase64Encoded():string;

    /**
     * @return bool
     */
    public function getIsNew():bool;
}
