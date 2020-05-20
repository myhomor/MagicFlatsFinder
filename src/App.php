<?php
namespace MagicFlatsFinder;

use MagicFlatsFinder\base as base;

/**
 * Class App
 * @package MagicFlatsFinder
 */
class App extends base\SimpleClass
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

    protected $xml_type = self::DEF_XML_TYPE;
    protected $filter;
    protected $discount;
    protected $debug = false;
    protected $parser;
    protected $sort;

    private $houseStack = [];
    private $map_buildings;
    private $map_buildings_id;

    public function init()
    {
        if (isset($this->config['xml_type']))
            $this->xml_type = $this->config['xml_type'];

        $this->filter = new base\Filter( $this->config );
        $this->parser = new base\Parser();


        if( isset( $this->config['map_buildings'] ) ) {
            $this->map_buildings = [];
            $this->map_buildings_id = [];
            foreach ( $this->config['map_buildings'] as $queue => $arBuilding ) {
                foreach ( $arBuilding as $h => $h_id )
                {
                    $this->map_buildings[ (int) $h ] = [
                        'queue' => (int) $queue,
                        'building_id' => (int) $h_id
                    ];

                    $this->map_buildings_id[ (int) $h_id ] = [
                        'queue' => (int) $queue,
                        'house_id' => (int) $h
                    ];
                }
            }
        }
    }

    public function addToStack( $house_id, $params )
    {
        $this->houseStack[$house_id] = $params;
    }

    public function findByStack( $params )
    {
        $xml = false;
        $this->sort = new base\Sort( $params['sort'] );

        $res = [ 'flats' => [], 'building' => [] ];
        $arAllSortApartments = [];

        foreach ( $this->houseStack as $house_id  => $stack) {

            $_params = $params;
            foreach ($stack as $_k_s => $_v_s)
                $_params[ $_k_s ] = $_v_s;

            $_params['select'] = ['flats','building'];
            $_params['_noFlatsManager'] = 'Y';

            $info = $this->find( $house_id, $_params );

            $res['flats'] = count( $info['flats'] ) ? array_merge( $res['flats'], $info['flats'] ) : $res['flats'];
            $res['building'][ $house_id ] = $info['building'];

        }

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


        if( !isset( $params['_noFlatsManager'] ) || $params['_noFlatsManager'] !== 'Y' )
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
                $arNames['price']               => [
                        'max' => base\Helper::getValue( $_building['maxPrice'] ),
                        'min' => base\Helper::getValue( $_building['minPrice'] )
                    ],

                $arNames['id']              => base\Helper::getValue($_building['id']),
                $arNames['name']            => base\Helper::getValue($_building['name']),
                $arNames['adressBuild']     => base\Helper::getValue($_building['adressBuild']),
                $arNames['status']          => base\Helper::getValue($_building['status']),
                $arNames['sectionCount']    => base\Helper::getValue($_building['sectionCount']),
                $arNames['floorsCount']     => base\Helper::getValue($_building['floorsCount']),
                $arNames['deliveryPeriod']  => base\Helper::getValue( $_building['deliveryPeriod'] ),
                $arNames['mountingBeginning'] => base\Helper::getValue( $_building['mountingBeginning'] ),

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

                $_apartment['buildNumber'] = (isset($params['build']['number'])
                        ? $params['build']['number']
                        : (preg_replace("/[^0-9]/", '', (string)$building->buildNumber)));

                $_apartment['building_id'] = (int)$building->id;

                $_apartment['queue'] = $params['queue']
                    ? $params['queue']
                    : (isset( $this->map_buildings_id[ $_apartment['building_id'] ] )
                        ? (int) $this->map_buildings_id[ $_apartment['building_id'] ]['queue']
                        : $this->queue );

                $plan = $this->helper->getFlatPlan( $_apartment['guid'],
                                                    (int)$building->id,
                                                    $this->config['project'],
                                                    (isset($params['plans']['format']) ? $params['plans']['format'] : false));

                $arNames = $this->fields->apartment;

                $_apartment['squareCommon'] = base\Helper::setSeparator( $_apartment['squareCommon'], ',');
                $_apartment['cost'] = str_replace(',', '.', $_apartment['cost']);
                $_apartment['squareMetrPrice'] = base\Helper::setSeparator( $_apartment['squareMetrPrice'], ',');
                //$_apartment['status'] = ($this->helper->simpleFlatStatus($_apartment['status'], 'free') ? 1 : 0);

                $arApartment = [
                    $arNames['id']              => base\Helper::getValue($_apartment['id']),
                    $arNames['queue']           => base\Helper::getValue($_apartment['queue']),
                    $arNames['guid']            => base\Helper::getValue($_apartment['guid']),
                    $arNames['building_id']     => base\Helper::getValue($_apartment['building_id']),
                    $arNames['section']         => base\Helper::getValue($_apartment['section']),
                    $arNames['floor']           => base\Helper::getValue($_apartment['floor']),
                    $arNames['numOrder']        => base\Helper::getValue($_apartment['numOrder']),
                    $arNames['squareCommon']    => base\Helper::getValue($_apartment['squareCommon']),
                    $arNames['rooms']           => base\Helper::getValue($_apartment['rooms']),
                    $arNames['cost']            => base\Helper::getValue($_apartment['cost']),
                    $arNames['squareMetrPrice'] => base\Helper::getValue($_apartment['squareMetrPrice']),
                    $arNames['status']          => ($this->helper->simpleFlatStatus($_apartment['status'], 'free') ? 1 : 0),
                    $arNames['crm_status']      => base\Helper::getValue($_apartment['status']),
                    $arNames['plan']            => $plan,
                    $arNames['numInPlatform']   => base\Helper::getValue($_apartment['numInPlatform']),
                    $arNames['is_apartament']   => $params['build']['is_apartament'] === true ? 'Y' : 'N',
                    $arNames['buildNumber']     => base\Helper::getValue($_apartment['buildNumber']),
                    $arNames['typeFinish']      => base\Helper::getValue($_apartment['typeFinish']),
                ];

                if( isset( $params['discount'] ) ) {
                    if( !key_exists( base\Discount::PARAM_TOTAL_COST, $_apartment ) )
                        $_apartment[ base\Discount::PARAM_TOTAL_COST ] = $_apartment['cost'];

                    if( $discount = $this->discount->setDiscount( $_apartment ) )
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

        $full_xml_file_type = false;
        if( isset( $this->config['full_xml_file'] ) ) {
            if( $this->parser->isXmlExists( $this->config['full_xml_file'] ) )
                $full_xml_file_type = base\Parser::TYPE_XML_FILE;
            elseif ( $this->parser->isUrlExists( $this->config['full_xml_file'] ) )
                $full_xml_file_type = base\Parser::TYPE_XML_LINK;
        }

        ///выгрузка недостающего объема квартир из локального xml файла, выгруженного из CRM
        if( !isset($params['active']) && $params['active'] !== true && $full_xml_file_type )
        {
            $exelFlats = $this->_findFlatsFromCrm( $this->config['full_xml_file'], $full_xml_file_type );

            foreach ($exelFlats as $key => $_apartment) {

                if( (int) $house_id !== $this->map_buildings[ $_apartment['info']['house'] ]['building_id'] )
                    continue;

                if( isset( $res['flats'][$_apartment['guid']] ) )
                    continue;

                $_apartment['buildNumber'] = $this->map_buildings[ $_apartment['info']['house'] ]['building_id'];
                $_apartment['section'] = $_apartment['info']['section'];
                $_apartment['floor'] = $_apartment['info']['floor'];
                $_apartment['numInPlatform'] = $_apartment['info']['pl'];


                $plan = $this->helper->getFlatPlan(
                    $_apartment['guid'],
                    $this->map_buildings[ $_apartment['info']['house'] ]['building_id'],
                    $this->config['project'],
                    (isset($params['plans']['format']) ? $params['plans']['format'] : false));


                $arApartment = [
                    //$arNames['id'] => $_apartment['id'],
                    $arNames['queue'] => $this->map_buildings[ $_apartment['info']['house'] ]['queue'],
                    $arNames['guid'] => $_apartment['guid'],
                    $arNames['building_id'] => $this->map_buildings[ $_apartment['info']['house'] ]['building_id'],
                    $arNames['section'] => $_apartment['section'],
                    $arNames['floor'] => $_apartment['floor'],
                    $arNames['numOrder'] => $_apartment['info']['number'],
                    $arNames['squareCommon'] => base\Helper::setSeparator($_apartment['square'], ','),
                    $arNames['rooms'] => $_apartment['room_count'],
                    $arNames['cost'] => base\Helper::setSeparator($_apartment['total_cost'], ','),
                    $arNames['squareMetrPrice'] => base\Helper::setSeparator($_apartment['cost_per_meter'], ','),
                    $arNames['status'] => 0,
                    $arNames['crm_status'] => 'n/a',
                    $arNames['plan'] => $plan,
                    $arNames['numInPlatform'] => $_apartment['numInPlatform'],
                    //$arNames['is_apartament'] => $params['build']['is_apartament'] === true ? 'Y' : 'N',
                    $arNames['buildNumber'] => $_apartment['buildNumber'],
                    $arNames['typeFinish'] => $_apartment['typeFinish'],

                ];

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

                   //$lim_apartment++;

                   /*if( isset( $params['limit'] ) )
                   {
                       if( (int) $lim_apartment >= (int) $params['limit'] )
                           break;
                   }*/
               }
            }
        }




        if( !isset( $params['_noFlatsManager'] ) || $params['_noFlatsManager'] !== 'Y' ) {
            if (isset($res['flats']))
                $res['flats'] = $this->resultFlatsManager($res['flats'], $params);
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


    /*метод выгружает все квартиры из локального файла xml выгрузки из CRM) или ссылки на него*/
    protected function _findFlatsFromCrm( $fileName, $file_type )
    {
        if( !$fileName  )
            return false;

        $arKeyCode = [
            'guid', 				//[0] => (Не изменять) Артикул
            'info', 				//[1] => Код объекта
            'room_count',		//[2] => Комнат
            'status',			//[3] => Состояние объекта
            'total_cost',		//[4] => Стоимость продажи
            'square',			//[5] => Количество
            'square_type',		//[6] => Единица объекта
            'cost_per_meter', //[7] => Цена продажи
            'type', 				//[8] => Тип объекта
            'type_id',			//[9] => Подтип объекта
            'address',			//[10] => Адрес (строение)
        ];

        $arKeyFlatID = [
            'house',
            'type_flat',
            'section',
            'floor',
            'pl',
            'number',
        ];

        $xml = new \SimpleXMLElement( $this->parser->loadXml( $fileName, $file_type )->asXml() );

        $ff=false;
        foreach ( $xml->Worksheet->Table->Row as $row )
        {
            if(!$ff){$ff=true; continue;}

            $i=0;
            foreach( $row->Cell as $oCell )
            {
                $data = (string) $oCell->Data;

                if( $i===1 )
                {
                    $data = explode('-', $data);
                    foreach ($data as $Dkey => $val)
                        $data_[ $arKeyFlatID[ $Dkey ] ] = $arKeyFlatID[ $Dkey ] === 'house' ? (int) preg_replace('/[^0-9]/', '', $val) : (int) $val;

                    $data = $data_;
                }

                $arCell[ $arKeyCode[$i++] ] = $data;
            }
            $arRowAll[] = $arCell;
            $arCell = [];
        }

        return $arRowAll;
    }


    public function resultFlatsManager( $arApartments, $params )
    {
        if( isset( $params['sort'] ) && count( $params['sort'] ) ){

            if( $arKeys = $this->sort->sort() ) {

                foreach ( $arKeys as $f_fk => $f_key ) {

                    if( isset( $arApartments[ $f_key ] ) ) {
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
