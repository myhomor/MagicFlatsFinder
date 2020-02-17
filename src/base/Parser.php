<?php

namespace MagicFlatsFinder\base;

class Parser
{
    public $debug = [];
    public $date_log_type = "Y-m-d H:i:s";

    public function log($text)
    {
        $this->debug[] = date( $this->date_log_type ) .' | '. $text;
    }

    public static function getXmlByUrl( $url, $isXml = true )
    {
        $xml = file_get_contents( $url );
        $xml = simplexml_load_string($xml);
        return $isXml ? $xml->asXml() : $xml;
    }

    public function saveXml($params, $xml_obj=false)
    {
        if( !$xml_obj )
        {

            $curl_handle=curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $params['xml_url'] );
            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);

            $xml = curl_exec($curl_handle);
            curl_close($curl_handle);

            $xml = simplexml_load_string($xml);

            $this->log( 'saveXml step 1' );
        }
        else
            $xml = $xml_obj;

        //$this->log( $xml->asXml( DIR_UPLOAD.'/xml/'.$params['xml_name'].'.xml') ? 'saveXml OK: '.$params['xml_name'].' XML SAVE!' : 'saveXml ERROR: '.$params['xml_name'].' XML NOT SAVE!' );

        return $xml;
    }

    public function loadXml( $xml_name )
    {
        if( !is_string($xml_name) || !$xml_name ) return false;

        $xml = false;
        if( file_exists( $xml_name ) )
        {
            $xml = file_get_contents( $xml_name );
            $xml = simplexml_load_string($xml);
            $this->log( 'loadXml OK: '.$xml_name.' load' );
        }
        else
            $this->log( 'loadXml ERROR: '.$xml_name.' IS NOT FOUND' );
        return $xml;
    }

    public function isXmlExists( $file_name )
    {
        return file_exists( $file_name );
    }

   /* public function setXmlName( $controller, $project )
    {
        return $controller.'_'.$project;
    }*/

}