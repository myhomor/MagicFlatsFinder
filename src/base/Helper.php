<?php

namespace MagicFlatsFinder\base;

use MagicFlatsFinder\App;

class Helper extends BaseObject
{
    const DEF_SEPARATOR = '.';
    public $plans_map;


    private $def_format = 'png';

    public $_fields_apartment = [];
    public $_fields_building = [];


    public function init()
    {
        $this->createPlansMap( $this->config['project'] );
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

    protected function createPlansMap( $project )
    {
        $this->plans_map[ $project ] = file_get_contents( $this->config['map'].'/map/?obj='.$project );
        if( $this->isJson( $this->plans_map[ $project ] ) )
        {
            $this->plans_map[ $project ] = json_decode( $this->plans_map[ $project ] );
        }
    }


    protected function isJson( $string )
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }


    public function getFlatPlan( $guid, $house_id, $project=false, $format = false )
    {
        if( !isset($guid) || !isset($house_id) ) {
            return false;
        }

        $format = !$format ? $this->def_format : $format;
        $project = !$project ? $this->config['project'] : $project;


        $format = $project === App::PROJECT_DOMBOR ? 'jpg' : $format;

        return $this->checkFlatPlan( $guid, $house_id, $project, $format )
            ? isset( $this->plans_map[ $project ]->{ $format }->{ $house_id }->{ $guid } )
                ? $this->plans_map[ $project ]->{ $format }->{ $house_id }->{ $guid }
                : $this->plans_map[ $project ]->{ $format }->{ 'all' }->{ $guid }
            : false;
    }


    public function checkFlatPlan( $guid, $house_id, $project=false, $format = false )
    {
        if( !isset($guid) ) {
            return false;
        }

        $format = !$format ? $this->def_format : $format;

        if( !isset( $this->plans_map[ $project ] ) ) {
            return false;
        }

        if( !isset( $this->plans_map[ $project ]->{ $format } ) ) {
            return false;
        }

        if( !isset( $this->plans_map[ $project ]->{ $format }->{ $house_id } ) ) {
            return isset( $this->plans_map[ $project ]->{ $format }->{ 'all' }->{ $guid } );
        } else {
            return isset( $this->plans_map[ $project ]->{ $format }->{ $house_id }->{ $guid } );
        }
    }


    public function checkFormat( $project, $format )
    {
        return (isset( $this->plans_map[ $project ] ) && isset( $this->plans_map[ $project ]->{ $format } ));
    }


    public function getListPlans( $project, $format )
    {
        return isset( $this->plans_map[ $project ] ) && isset( $this->plans_map[ $project ]->{ $format } ) ? $this->plans_map[ $project ]->{ $format } : false;
    }


    public function getListFormats( $project )
    {
        if( !isset( $this->plans_map[ $project ] ) )
            return false;

        $arFormat = [];

        foreach ( (array) $this->plans_map[ $project ] as $format => $arr )
            $arFormat[] = $format;

        return $arFormat;
    }


    public function getFlatMixKey( $_apartment )
    {
        return $_apartment[ $this->_fields_apartment['building_id'] ] . '_' . $_apartment[ $this->_fields_apartment['section'] ] . '_' . $_apartment[ $this->_fields_apartment['floor'] ] . '_' . $_apartment[ $this->_fields_apartment['numOrder'] ];
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

