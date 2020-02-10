<?php

namespace MagicFlatsFinder\base;

class BaseObject
{
    public $config;

    public function __construct($config = [])
    {
        /*if ( empty($config) )
            $this->config = self::configure($this, $config);
        else
            $this->config  = $config;*/

        $this->config  = $config;

        $this->init();
    }

    /*public function configure()
    {
        if( file_exists( __DIR__.'/../config.php' ) )
            return include ( __DIR__.'/../config.php' );
    }*/

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