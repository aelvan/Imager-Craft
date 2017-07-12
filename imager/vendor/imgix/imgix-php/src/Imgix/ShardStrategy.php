<?php

namespace Imgix;

abstract class ShardStrategy {
    const CRC = 1;
    const CYCLE = 2;
}