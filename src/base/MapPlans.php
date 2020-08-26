<?
namespace MagicFlatsFinder\base;

use MagicFlatsFinder\App;

class MapPlans extends SimpleClass
{
    public $plans_map;

    private $def_format = 'png';

    public $elastic_search = false;
    public $elastic_index = false;

    public function init()
    {
        if( ElasticSearch::checkIssetCloudParams( $this->config['elastic_search'] ) )
        {
            $this->elastic_search = new ElasticSearch();
            $this->elastic_search->cloud(
                $this->config['elastic_search']['cloudId'],
                $this->config['elastic_search']['username'],
                $this->config['elastic_search']['password']
            );

            $this->elastic_index = ElasticSearch::getIndexName( $this->config['project'], 'plans_flats' );
        }

        if( $this->config['map_link'] )
            $this->createPlansMap( $this->config['project'] );
    }

    protected function createPlansMap( $project )
    {
        $this->plans_map[ $project ] = file_get_contents( $this->config['map_link'] );

        //var_dump( json_decode( $this->plans_map[ $project ] ) );
        if( $this->helper->isJson( $this->plans_map[ $project ] ) )
            $this->plans_map[ $project ] = Helper::object_to_array( json_decode( $this->plans_map[ $project ] ) );
    }

    public function getFlatPlan( $guid, $house_id, $project=false, $format = false )
    {
        if( !isset($guid) || !isset($house_id) ) {
            return false;
        }

        $format = !$format ? $this->def_format : $format;
        $project = !$project ? $this->config['project'] : $project;

        $format = $project === App::PROJECT_DOMBOR ? 'jpg' : $format;

        return $this->checkFlatPlan($guid, $house_id, $project, $format)
            ? isset( $this->plans_map[$project][$format][$house_id][$guid] )
                ? $this->plans_map[$project][$format][$house_id][$guid]
                : $this->plans_map[$project][$format]['all'][$guid]
            : false;
    }

    public function addMapPlansByHouse( $house_id, $format=false )
    {
        if( !isset($house_id) ) return false;

        $format = !$format ? $this->def_format : $format;
        $format = !$format && $this->config['project'] === App::PROJECT_DOMBOR ? 'jpg' : $format;

        if( $this->elastic_search->indexExists( $this->elastic_index ) )
        {
            $filter = [
                'type' => '_doc',
                'from' => 0,
                'size' => 10000,
                'index'=> $this->elastic_index
            ];

            $filter['body']['query']['bool']['must'][]['match'][ 'format' ] = $format;
            $filter['body']['query']['bool']['must'][]['terms'][ 'building_id' ][] = (int) $house_id;

            $plans = $this->elastic_search->search( $filter );

            if( count( $plans ) ) {
                foreach ( $plans as $planInfo )
                    $this->plans_map[ $this->config['project'] ][$format][$house_id][$planInfo['guid']] = $planInfo['plan'];
            }
        }

    }

    public function getFlatPlanByElasticSearch( $guid, $house_id, $project=false, $format = false )
    {
        if( !isset($guid) || !isset($house_id) ) {
            return false;
        }

        $format = !$format ? $this->def_format : $format;
        $project = !$project ? $this->config['project'] : $project;

        $format = $project === App::PROJECT_DOMBOR ? 'jpg' : $format;

        if( $this->elastic_search->indexExists( $this->elastic_index ) )
        {
            $filter = [
                'type' => '_doc',
                'from' => 0,
                'size' => 10000,
                'index'=> $this->elastic_index
            ];

            $filter['body']['query']['bool']['must'][]['match'][ 'guid' ] = $guid;
            $filter['body']['query']['bool']['must'][]['match'][ 'format' ] = $format;

            $this->plans_map[ $project ] = $this->elastic_search->search( $filter );

            echo "<pre>".print_r( $this->plans_map,true )."</pre>";
            echo "<pre>".print_r( $this->elastic_search->search( $filter ),true )."</pre>";
            die();
        }
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

        if( !isset( $this->plans_map[ $project ][$format] ) ) {
            return false;
        }

        if( !isset( $this->plans_map[ $project ][$format][$house_id] ) ) {
            return isset( $this->plans_map[ $project ][$format]['all'][$guid] );
        } else {
            return isset( $this->plans_map[ $project ][$format][$house_id][$guid] );
        }
    }


    public function checkFormat( $project, $format )
    {
        return (isset( $this->plans_map[ $project ] ) && isset( $this->plans_map[ $project ][$format] ));
    }


    public function getListPlans( $project, $format )
    {
        return isset( $this->plans_map[ $project ] ) && isset( $this->plans_map[ $project ][$format] ) ? $this->plans_map[ $project ][$format] : false;
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
}