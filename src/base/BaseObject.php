<?php

namespace MagicFlatsFinder\base;

class BaseObject
{
    public $config;

    public function __construct($config = [])
    {
        $this->config  = $config;

        $this->init();
    }

    /**
     * Инициализация объекта после конструктора
     */
    public function init()
    {

    }

    public static function className()
    {
        return get_called_class();
    }

    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }

    public function canSetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'set' . $name) || $checkVars && property_exists($this, $name);
    }

    public function canGetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name);
    }

    public function hasProperty($name, $checkVars = true)
    {
        return $this->canGetProperty($name, $checkVars) || $this->canSetProperty($name, false);
    }

}