<?php
namespace MagicFlatsFinder;

use MagicFlatsFinder\base as base;

/**
 * Class App
 * @package MagicFlatsFinder
 */
class App extends base\BaseObject
{

    const PROJECT_DOMUSKVERA = 'domskver';
    const PROJECT_DOMBOR = 'dombor';
    const PROJECT_DOM128 = 'dom128';
    const PROJECT_HEADLINER = 'headliner';
    const PROJECT_AKADEM = 'akadem';
    const PROJECT_GULLIVER = 'gulliverperm';
    const PROJECT_ROYALPARK = 'royalpark';
    const PROJECT_ILOVE = 'ilove';
    const PROJECT_BAUMANHAUSE = 'baumanhouse';
    const PROJECT_HLGULLIVER = 'hlgulliver';

    const DEF_XML_TYPE = 'crmweb';

    public $deep = 4;
    public $queue = 1;
    public $from_id_start = 0;

    protected $helper;
    protected $xml_type = self::DEF_XML_TYPE;
    protected $filter;
    protected $discount;
    protected $fields;
    protected $debug = false;
    protected $parser;
    protected $sort;

    private $houseStack = [];

    public function init()
    {
        $this->debug = $this->config['debug'] ? $this->config['debug'] : false;

        if (isset($this->config['xml_type']))
            $this->xml_type = $this->config['xml_type'];

        $this->helper = new base\Helper([
            'project' => $this->config['project'],
            'map' => 'http://feeds.dev.kortros.ru'
        ]);

        $this->filter = new base\Filter();
        $this->fields = new base\Fields( ( isset($this->config['fields_tmp']) ? $this->config['fields_tmp'] : [] ) );
        $this->parser = new base\Parser();
    }

    public function addToStack( $house_id, $params )
    {
        $this->houseStack[$house_id] = $params;
    }

    public function findByStack( $params )
    {
        $xml = false;
        $this->sort = new base\Sort( $params['sort'] );
        $this->discount = new base\Discount( $params['discount'] );

        $res = [ 'flats' => [], 'building' => [] ];
        $arAllSortApartments = [];

        foreach ( $this->houseStack as $house_id  => $stack) {

            if( isset( $stack['xml_file'] ) && $this->parser->isXmlExists( $stack['xml_file'] ) ) {
               $xml = new base\SimpleXmlExtended( $this->parser->loadXml( $stack['xml_file'] )->asXml() );
            }

            if( !$xml ){
                if ( $this->config['project'] === self::PROJECT_HEADLINER && $this->xml_type !== self::DEF_XML_TYPE )
                    $xml = new base\SimpleXmlExtended( base\Parser::getXmlByUrl( $this->config['xml'] ) );
                else
                    $xml = new base\SimpleXmlExtended( base\Parser::getXmlByUrl($this->config['xml'] . '?BuildingID=' . $house_id . '&deep=' . $this->deep ) );
            }

            $_params = $params;
            foreach ($stack as $_k_s => $_v_s)
                $_params[ $_k_s ] = $_v_s;

            $_params['select'] = ['flats','building'];

            $info = $this->_find( $house_id, $_params, $xml );

            $res['flats'] = count( $info['flats'] ) ? array_merge( $res['flats'], $info['flats'] ) : $res['flats'];
            $res['building'][ $house_id ] = $info['building'];

        }

        if( ( isset($params['sort']) && count($params['sort']) ) || isset( $params['limit'] ) ) {

            if( isset($params['sort']) && count($params['sort']) ) {

                if( $arKeys = $this->sort->sort() )
                {
                    $lim_apartment = 0;
                    foreach ( $arKeys as $f_key ) {
                        if( isset( $res['flats'][ $f_key ] ) ) {
                            $arSortApartments[$f_key] = $res['flats'][ $f_key ];
                            $lim_apartment++;

                            if( isset( $params['limit'] ) )
                            {
                                if( (int) $lim_apartment >= (int) $params['limit'] )
                                    break;
                            }
                        }
                    }
                }

            }elseif( isset( $params['limit'] ) ){

                $lim_apartment = 0;
                foreach ( $res['flats'] as $f_key => $flat) {
                    $arSortApartments[$f_key] = $flat;
                    $lim_apartment++;
                    if( (int) $lim_apartment >= (int) $params['limit'] )
                        break;
                }
            }

            if( isset( $arSortApartments ) )
                $arAllSortApartments = array_merge($arAllSortApartments, $arSortApartments);

            if( isset( $arAllSortApartments ) && count( $arAllSortApartments ) )
                $res['flats'] = $arAllSortApartments;
        }

        if( !isset($params['select']) )
            return $res;

        foreach ($params['select'] as $type) {
            if (isset($res[$type])) {
                $arRes[$type] = $res[$type];
            }
        }

        return count($params['select']) == 1 ? $arRes[$type] : $arRes;
    }


    /**
     * @param $house_id integer id строения из crm
     * @param $params array массив с параметрами фильтрации
     * @return mixed array
     *
     * Единая точка входа в приложение.
     * На вход подается id строения и массив параметров для фильтрации
     */
    public function find($house_id, $params)
    {
        $xml = false;
        if( isset( $params['xml_file'] ) && $this->parser->isXmlExists( $params['xml_file'] ) )
        {
            $xml = new base\SimpleXmlExtended( $this->parser->loadXml( $params['xml_file'] )->asXml() );
        }

        if( !$xml ){
            if ( $this->config['project'] === self::PROJECT_HEADLINER && $this->xml_type !== self::DEF_XML_TYPE )
                $xml = new base\SimpleXmlExtended( base\Parser::getXmlByUrl( $this->config['xml'] ) );
            else
                $xml = new base\SimpleXmlExtended( base\Parser::getXmlByUrl($this->config['xml'] . '?BuildingID=' . $house_id . '&deep=' . $this->deep ) );
        }

        $this->sort = new base\Sort( $params['sort'] );
        return $this->_find($house_id, $params, $xml);
    }

    /**
     * @param $house_id
     * @param $params
     * @param $xml
     * @return mixed
     * Метод поиска квартир по фиду
     */
    protected function _find($house_id, $params, $xml)
    {
        $this->discount = new base\Discount( $params['discount'] );

        foreach ($xml->developers->developer->projects->project->buildings->building as $building) {
            if ((int)$building->id !== $house_id) continue;

            $_building = (array)$building;
            $_apartments = (array)$building->apartments;

            $arNames = $this->fields->building;

            $res['building'] = [
                $arNames['price'] => ['max' => $_building['maxPrice'], 'min' => $_building['minPrice']],
                $arNames['id'] => $_building['id'],
                $arNames['name'] => $_building['name'],
                $arNames['adressBuild'] => $_building['adressBuild'],
                $arNames['status'] => $_building['status'],
                $arNames['sectionCount'] => $_building['sectionCount'],
                $arNames['floorsCount'] => $_building['floorsCount'],
                $arNames['deliveryPeriod'] => $_building['deliveryPeriod'],
                $arNames['mountingBeginning'] => $_building['mountingBeginning'],

                $arNames['countAll'] => $_apartments['countAll'],

                $arNames['count_'] => [
                    1 => (int) ( $_apartments['count1'] ) ? $_apartments['count1'] : 0,
                    2 => (int) ( $_apartments['count2'] ) ? $_apartments['count2'] : 0,
                    3 => (int) ( $_apartments['count3'] ) ? $_apartments['count3'] : 0,
                    4 => (int) ( $_apartments['count4'] ) ? $_apartments['count4'] : 0,
                    5 => (int) ( $_apartments['count5'] ) ? $_apartments['count5'] : 0,
                ],

                $arNames['quantity_'] => [
                    1 => (int) ( $_apartments['quantity1'] ) ? base\Helper::setSeparator( $_apartments['quantity1'], ',') : 0,
                    2 => (int) ( $_apartments['quantity2'] ) ? base\Helper::setSeparator( $_apartments['quantity2'], ',') : 0,
                    3 => (int) ( $_apartments['quantity3'] ) ? base\Helper::setSeparator( $_apartments['quantity3'], ',') : 0,
                    4 => (int) ( $_apartments['quantity4'] ) ? base\Helper::setSeparator( $_apartments['quantity4'], ',') : 0,
                    5 => (int) ( $_apartments['quantity5'] ) ? base\Helper::setSeparator( $_apartments['quantity5'], ',') : 0,
                ],

                $arNames['price_'] => [
                    1 => (int) ( $_apartments['price1'] ) ? base\Helper::setSeparator( $_apartments['price1'], ',') : 0,
                    2 => (int) ( $_apartments['price2'] ) ? base\Helper::setSeparator( $_apartments['price2'], ',') : 0,
                    3 => (int) ( $_apartments['price3'] ) ? base\Helper::setSeparator( $_apartments['price3'], ',') : 0,
                    4 => (int) ( $_apartments['price4'] ) ? base\Helper::setSeparator( $_apartments['price4'], ',') : 0,
                    5 => (int) ( $_apartments['price5'] ) ? base\Helper::setSeparator( $_apartments['price5'], ',') : 0,
                ],


                $arNames['countTotal'] => $_apartments['countTotal'],
            ];

            $lim_apartment = 0;
            foreach ($building->apartments->apartment as $apartment) {
                $_apartment = (array) $apartment;

                if (isset($params['active']) && $params['active'] === true) {
                    if (!$this->helper->simpleFlatStatus($_apartment['status'], 'free'))
                        continue;
                }


                $_apartment['queue'] = $params['queue'] ? $params['queue'] : $this->queue;

                $_apartment['buildNumber'] = (isset($params['build']['number'])
                        ? $params['build']['number']
                        : (preg_replace("/[^0-9]/", '', (string)$building->buildNumber)));


                $plan = $this->helper->getFlatPlan( $_apartment['guid'],
                                                    (int)$building->id,
                                                    $this->config['project'],
                                                    (isset($params['plans']['format']) ? $params['plans']['format'] : false));
                $arNames = $this->fields->apartment;
                $arApartment = [
                    $arNames['id'] => $_apartment['id'],
                    $arNames['queue'] => $_apartment['queue'],
                    $arNames['guid'] => $_apartment['guid'],
                    $arNames['building_id'] => (int)$building->id,
                    $arNames['section'] => $_apartment['section'],
                    $arNames['floor'] => $_apartment['floor'],
                    $arNames['numOrder'] => $_apartment['numOrder'],
                    $arNames['squareCommon'] => base\Helper::setSeparator( $_apartment['squareCommon'], ','),
                    $arNames['rooms'] => $_apartment['rooms'],
                    $arNames['cost'] => str_replace(',', '.', $_apartment['cost']),
                    $arNames['squareMetrPrice'] => base\Helper::setSeparator( $_apartment['squareMetrPrice'], ','),
                    $arNames['status'] => ($this->helper->simpleFlatStatus($_apartment['status'], 'free') ? 1 : 0),
                    $arNames['crm_status'] => $_apartment['status'],
                    $arNames['plan'] => $plan,
                    $arNames['numInPlatform'] => $_apartment['numInPlatform'],
                    $arNames['is_apartament'] => $params['build']['is_apartament'] === true ? 'Y' : 'N',
                    $arNames['buildNumber'] => $_apartment['buildNumber'],
                    $arNames['typeFinish'] => $_apartment['typeFinish'],
                ];

                if( isset( $params['discount'] ) ) {
                    if( !key_exists( base\Discount::PARAM_TOTAL_COST, $arApartment ) )
                        $arApartment[ base\Discount::PARAM_TOTAL_COST ] = $arApartment[ $arNames['cost'] ];

                    if( $discount = $this->discount->setDiscount( $arApartment ) )
                        $arApartment['discount'] = $discount;
                }

                $isFiltered = false;

                if( ( isset( $params['filter'] ) && count( $params['filter'] ) && $this->filter->check( 'flat', $arApartment, $params['filter'] ) ) ){

                    if( $this->debug )
                            $arApartment['debug'] = $this->filter->debug;

                    $res['flats'][$_apartment['guid']] = $arApartment;
                    $isFiltered = true;
                }


                if( !isset( $params['filter'] ) && !count( $params['filter'] ) ) {
                    $res['flats'][$_apartment['guid']] = $arApartment;
                    $isFiltered = true;
                }


                if( $isFiltered ) {
                    if (isset($params['sort']) && count($params['sort']))
                        $this->sort->addElementToSort($arApartment);

                    $lim_apartment++;

                    if( isset( $params['limit'] ) )
                    {
                        if( (int) $lim_apartment >= (int) $params['limit'] )
                            break;
                    }
                }
            }
        }

        if( isset($res['flats']) )
            $res['flats'] = $this->resultFlatsManager( $res['flats'], $params );

        if( !isset($params['select']) )
            return $res;

        foreach ($params['select'] as $type) {
            if (isset($res[$type])) {
                $arRes[$type] = $res[$type];
            }
        }

        return count($params['select']) == 1 ? $arRes[$type] : $arRes;
    }

    public function resultFlatsManager( $arApartments, $params )
    {
        if( isset( $params['sort'] ) && count( $params['sort'] ) ){
            if( $arKeys = $this->sort->sort() ) {
                foreach ( $arKeys as $f_key ) {
                    if( isset( $res['flats'][ $f_key ] ) ) {
                        //$arSortApartments[$f_key] = [$this->sort->by => $res['flats'][$f_key][$this->sort->by] . ' || ' . ( int)$res['flats'][$f_key][$this->sort->by]];
                        $arSortApartments[$f_key] = $arApartments[ $f_key ];
                    }
                }

                if( isset( $arSortApartments ) )
                    $arApartments = $arSortApartments;
            }
        }

        if( $params['from'] || $params['limit']  ){

            $lim_apartment = 0;
            $from_id = $this->from_id_start;
            foreach ( $arApartments as $guid => $arApartment ) {

                if( isset( $params['from'] ) && (int) $params['from'] >= 0 ) {

                    if( (int) $from_id < (int) $params['from'] ){
                        $from_id++;
                        continue;
                    }
                }

                $lim_apartment++;
                $arResApart[$guid] = $arApartment;

                if( isset( $params['limit'] ) && (int) $params['limit'] > 0 ) {
                    if ( (int)$lim_apartment >= (int)$params['limit'] )
                        break;
                }
                $from_id++;
            }

            if( isset($arResApart) && count( $arResApart ) ) {
                $arApartments = $arResApart;
            }
        }

        return $arApartments;
    }

}
