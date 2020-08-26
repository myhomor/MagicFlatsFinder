<?php

namespace MagicFlatsFinder\base;

use MagicFlatsFinder\App;

class Helper extends BaseObject
{
    const DEF_SEPARATOR = '.';

    public $_fields_apartment = [];
    public $_fields_building = [];


    public static function object_to_array($data)
    {
        if (is_array($data) || is_object($data))
        {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = self::object_to_array($value);
            } return $result;
        }
        return $data;
    }

    public function simpleFlatStatus( $status, $type = 'free' )
    {
        switch ( $type ){
            case 'free':
                return (is_null($status) ? true : ( !$status || $status === '0'  ? true : false ) );
        }
    }

    public function getSimpleFlatOralReservStatus( $status, $status2 )
    {
        return (is_null($status) || is_null($status2) ? false : ( ($status === 1 || $status === '1') && ($status2 === 1 || $status2 === '1') ? true : false ) );
    }

    public function isJson( $string )
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }


    public function getFlatMixKey( $_apartment, $_numOrder_clear_zero=false )
    {
        if( $_numOrder_clear_zero )
            $numOrder = (int) $_apartment[ $this->_fields_apartment['numOrder'] ];
        else
            $numOrder = $_apartment[ $this->_fields_apartment['numOrder'] ];

        return $_apartment[ $this->_fields_apartment['building_id'] ] . '_' . $_apartment[ $this->_fields_apartment['section'] ] . '_' . $_apartment[ $this->_fields_apartment['floor'] ] . '_' . $numOrder;
    }

    public static function setSeparator( $value, $old_separator, $new_separator=false )
    {
        return str_replace($old_separator, ( $new_separator ? $new_separator : self::DEF_SEPARATOR ), $value);
    }

    public static function getValue( $param, $def_val = '' )
    {
        return gettype( $param ) === 'object'
                ? $def_val
                : isset( $param ) && !is_null( $param )
                    ? $param : $def_val;
    }

}

