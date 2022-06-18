<?php

namespace LionRoute\Traits;

trait Singleton {

    private static $singleton = false;

    final private function __construct() {

    }

    final public static function getInstance() {
        if (self::$singleton === false) {
            self::$singleton = new self();
        }

        return self::$singleton;
    }

}