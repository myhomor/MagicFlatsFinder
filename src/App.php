<?php
namespace MagicFlatsFinder;

use MagicFlatsFinder\base as base;

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

    protected $helper;
    protected $xml_type = self::DEF_XML_TYPE;

    //public $project;

    public function init()
    {
        if( isset($this->config['xml_type']) )
            $this->xml_type = $this->config['xml_type'];

        $this->helper = new base\Helper( [
            'project' => $this->config['project'],
            'map' => 'http://feeds.dev.kortros.ru'
        ] );
    }

    public function find( $house_id, $params )
    {
        if( $this->config['project'] === self::PROJECT_HEADLINER && $this->xml_type !== self::DEF_XML_TYPE )
            $xml = new base\SimpleXmlExtended( base\Parser::getXmlByUrl( $this->config['xml'] ) );
        else
            $xml = new base\SimpleXmlExtended( base\Parser::getXmlByUrl( $this->config['xml'].'?BuildingID='.$house_id.'&deep='.$this->deep ) );


        return $this->_find( $house_id, $params, $xml );
    }

    protected function _find( $house_id, $params, $xml )
    {
        foreach ( $xml->developers->developer->projects->project->buildings->building as $building )
        {
            if( (int) $building->id !== $house_id ) continue;

            $_building = (array)$building;
            $_apartments = (array)$building->apartments;


            $res['building'] = [
                'price' => ['max' => $_building['maxPrice'], 'min' => $_building['minPrice']],
                'id' => $_building['id'],
                'name' => $_building['name'],
                'address' => $_building['adressBuild'],
                'status' => $_building['status'],
                'sectionCount' => $_building['sectionCount'],
                'floorsCount' => $_building['floorsCount'],
                'deliveryPeriod' => $_building['deliveryPeriod'],
                'mountingBeginning' => $_building['mountingBeginning'],

                'countFree' => $_apartments['countAll'],
                'count' => [    1 => $_apartments['count1'],
                                2 => $_apartments['count2'],
                                3 => $_apartments['count3'],
                                4 => $_apartments['count4'],
                                5 => $_apartments['count5'],
                ],

                'quantity' => [ 1 => $_apartments['quantity1'],
                                2 => $_apartments['quantity2'],
                                3 => $_apartments['quantity3'],
                                4 => $_apartments['quantity4'],
                                5 => $_apartments['quantity5'],
                ],

                'flatsCost' => [1 => $_apartments['price1'],
                                2 => $_apartments['price2'],
                                3 => $_apartments['price3'],
                                4 => $_apartments['price4'],
                                5 => $_apartments['price5'],
                ],

                'countTotal' => $_apartments['countTotal'],
            ];


            foreach ($building->apartments->apartment as $apartment) {
                $_apartment = (array)$apartment;

                if ( isset( $params['active']) && $params['active'] === true )
                {
                    if ( !$this->helper->simpleFlatStatus( $_apartment['status'], 'free') )
                        continue;
                }

                # придумать составной ключ keyType
                $filter_error = false;
                if (isset($params['flats'])) {
                    if ($params['flats']['keyType'] === 'mixed' && count($params['flats']['list'])) {
                        $b_id = (int)$building->id;
                        if (!in_array(($b_id . '_' . $_apartment['section'] . '_' . $_apartment['floor'] . '_' . $_apartment['numOrder']), $params['flats']['list']))
                            $filter_error = true;
                    }

                    if ($params['flats']['keyType'] === 'if' && isset($params['flats']['filter'])) {
                        foreach ($params['flats']['filter'] as $key => $val):

                            if (isset($_apartment[$key])) {
                                foreach ($val as $__key => $__val) {
                                    if ($__key === '>' && (int)$_apartment[$key] < (int)$__val) $filter_error = true;
                                    if ($__key === '<' && (int)$_apartment[$key] > (int)$__val) $filter_error = true;
                                    if ($__key === '=' && (int)$_apartment[$key] !== (int)$__val) $filter_error = true;
                                }
                            }

                        endforeach;
                    }
                }

                if (!$filter_error) {

                    //if( !isset( $params['ApartmentsGuiD'] ) )
                    //   $arApartmentGuid[ $_apartment['guid'] ] = $_apartment['guid'];

                    $_apartment['queue'] = $params['queue'] ? $params['queue'] : $this->queue;

                    //$_apartment['buildNumber'] = ( preg_replace("/[^0-9]/", '', (string) $building->buildNumber ));
                    $_apartment['buildNumber'] = ( isset( $params['build']['number'] )
                        ? $params['build']['number']
                        : (preg_replace("/[^0-9]/", '', (string)$building->buildNumber)));


                    //$arBuildList[$_apartment['buildNumber']] = $_apartment['queue'];

                    //$plan = $this->getPlan( $_GET['obj'], $_apartment );
                    //$plan = $plan ? $plan : $this->helper->flats->getFlatPlan( getCNFkeyByGETkey( $_GET['obj'] ), $_apartment, $planInfo['format'], $planInfo['file_name'] );

                    ///Переделать
                    /*
                    $planInfo = getPlanInfoByKey(getCNFkeyByGETkey($project), $_apartment);
                    $plan = ($project === GET_HEADLINER || $project === GET_ILOVE || $project === GET_BAUMANHOUSE
                        ? $this->system->getSLink() . '/' . DIR_UPLOAD_PLANS . $IMG_PATH . $this->getFlatPlan(getCNFkeyByGETkey($project), $_apartment, (isset($params['plans']['format']) ? $params['plans']['format'] : false))
                        : $this->getFlatPlan(getCNFkeyByGETkey($project), $_apartment, $planInfo['format'], $planInfo['file_name']));
                    */

                    //$res['apartaments'][ $_apartment['guid'] ] = [
                    $arApartment = [
                        'queue' => $_apartment['queue'],
                        'guid' => $_apartment['guid'],
                        'building_id' => (int) $building->id,
                        'section_number' => $_apartment['section'],
                        'floor_number' => $_apartment['floor'],
                        'number' => $_apartment['numOrder'],
                        'square' => $_apartment['squareCommon'],
                        'room_count' => $_apartment['rooms'],
                        'total_cost' => str_replace(',', '.', $_apartment['cost']),
                        'cost_per_meter' => $_apartment['squareMetrPrice'],
                        'status' => ( $this->helper->simpleFlatStatus( $_apartment['status'], 'free' ) ? 1 : 0 ),
                        'crm_status' => $_apartment['status'],

                        'plan' => $this->helper->getFlatPlan(   $_apartment['guid'],
                                                                (int) $building->id,
                                                                $this->config['project'],
                                                                ( isset( $params['plans']['format'] ) ? $params['plans']['format'] : false ) ),

                        'pl' => $_apartment['numInPlatform'],
                        'is_apartament' => $params['build']['is_apartament'] === true ? 'Y' : 'N',
                        'buildNumber' => $_apartment['buildNumber'],
                        "type_finish" => $_apartment['typeFinish'],
                    ];

                    if (isset($params['discount'])) {
                        if (isset($params['discount']['all'])) {
                            $arApartment['old_total_cost'] = $arApartment['total_cost'];
                            $total_cost = (int)$arApartment['total_cost'];
                            $arApartment['total_cost'] = $total_cost - (($total_cost / 100) * ( int )$params['discount']['all']);
                            $arApartment['discount'] = ( int )$params['discount']['all'];

                            $arApartment['discount_cost'] = ($total_cost / 100) * ( int )$params['discount']['all'];

                        } elseif (count($params['discount']['list'])) {
                            if ($params['discount']['keyType'] === 'mixed') {
                                $b_id = (int)$building->id;
                                if (array_key_exists($b_id . '_' . $_apartment['section'] . '_' . $_apartment['floor'] . '_' . $_apartment['numOrder'], $params['discount']['list'])) {
                                    $arApartment['old_total_cost'] = $arApartment['total_cost'];
                                    $total_cost = (int)$arApartment['total_cost'];
                                    $arApartment['total_cost'] = $total_cost - (($total_cost / 100) * ( int )$params['discount']['list'][$b_id . '_' . $_apartment['section'] . '_' . $_apartment['floor'] . '_' . $_apartment['numOrder']]);
                                    $arApartment['discount'] = $params['discount']['list'][$b_id . '_' . $_apartment['section'] . '_' . $_apartment['floor'] . '_' . $_apartment['numOrder']];

                                    $arApartment['discount_cost'] = ($total_cost / 100) * ( int )$params['discount']['list'][$b_id . '_' . $_apartment['section'] . '_' . $_apartment['floor'] . '_' . $_apartment['numOrder']];
                                }
                            }
                        }
                    }

                    $res['flats'][ $_apartment['guid'] ] = $arApartment;
                }
            }
        }
        if( !isset( $params['select'] ) )
            return $res;

        foreach ( $params['select'] as $type)
        {
            if( isset( $res[ $type ] ) ) {
                $arRes[$type] = $res[$type];
            }
        }

        return count($params['select']) == 1 ? $arRes[ $type ] : $arRes;
    }

    protected function createMainXMl( $project, $XML_KEY, $params=[], $deep=4, $xmlType = 'art3d' )
    {
        if( !$project || !is_string( $project ) || !$XML_KEY )
            return false;

        $XML_PARAMS = $this->config['XML_PARAMS'][ $XML_KEY ];
        $OBJ_INFO = $this->config['OBJECT_INFO'][ $XML_KEY ];

        $obXmlType = $this->XmlBuilder->setXMLType( $xmlType );
        //$XML = $obXmlType->getXMLHeader([]);

        $IMG_PATH = $XML_PARAMS[ 'IMG_PATH' ];

        if( isset( $params['plans'] ) ) {

            if( isset( $params['plans']['format'] ) && is_dir( $_SERVER['DOCUMENT_ROOT'].'/'.DIR_UPLOAD_PLANS.'/'.$_GET['obj'].'_'.$params['plans']['format'] )  )
                $IMG_PATH = '/'.$_GET['obj'].'_'.$params['plans']['format'].'/';
        }

        switch ( $project )
        {
            case GET_DOMBOR:
            case GET_DOMSKVER:
            case GET_DOM128:
            case GET_HEADLINER:
            case GET_AKADEM:
            case GET_ILOVE:
            case GET_GULLIVER:
            case GET_ROPARK:
            case GET_BAUMANHOUSE:
            case GET_HLGULLIVER:

                if( $project === GET_HEADLINER )
                    $xml = new SimpleXMLExtended( $this->CParser->getXmlByUrl( $this->config['FEEDS'][ $XML_KEY ]['SITE'] ) );

                if( is_string( $XML_PARAMS['MAIN']['BuildingID'] ) || is_integer( $XML_PARAMS['MAIN']['BuildingID'] ) )
                    $XML_PARAMS['MAIN']['BuildingID'] = [ 1 => [ $XML_PARAMS['MAIN']['BuildingID'] ] ];



                $XML .= '<apartments>';
                foreach ( $XML_PARAMS['MAIN']['BuildingID'] as $queue => $arBuildingID ) {
                    foreach ( $arBuildingID as $BuildingID )
                    {
                        if( !isShowBuildInController( $XML_PARAMS['MAIN']['CORP_INFO'][$BuildingID]['showInController'], $this->prefix, $_GET['team'] ) )
                            continue;

                        if( $project !== GET_HEADLINER )
                            $xml = new SimpleXMLExtended( $this->CParser->getXmlByUrl( $this->config['MAIN_FEED'].'?BuildingID='.$BuildingID.'&deep='.$deep ) );

                        $params['XML_PARAMS'] = $XML_PARAMS;
                        $params['queue'] = $queue;

                        $arXmlInfo = $this->helper->flats->getInfoFromXMLFile( $project, $BuildingID, $xml, $params );

                        //echo "<pre>".print_r($arXmlInfo,true)."</pre>";

                        foreach ( $arXmlInfo as $b_id => $_building )
                        {
                            $_apartments = $_building['apartments'];
                            unset( $_building['apartments'] );

                            if( isset( $XML_PARAMS['MAIN']['CORP_INFO'][ $_building['id'] ]['address'] ) )
                                $_building['adress'] = $XML_PARAMS['MAIN']['CORP_INFO'][ $_building['id'] ]['address'];

                            $XML_BUILDING[] = $obXmlType->getTag('building', $_building );

                            //echo "<pre>".print_r($arXmlInfo,true)."</pre>";

                            foreach ($_apartments as $guid => $apartment)
                            {
                                $arApartmentGuid[ $apartment['guid'] ] = $apartment['guid'];
                                $arBuildList[ $apartment['buildNumber'] ] = $queue;


                                $XML .= $obXmlType->getTag( 'apartment', $apartment );
                            }
                        }

                        //echo "<pre>".print_r($arXmlInfo,true)."</pre>";

                    }
                }

                ///выгрузка недостающего объема квартир из локального xml файла, выгруженного из CRM
                if( isset($XML_PARAMS['MAIN']['LOAD_FLATS_FROM_FILE'])
                    && $XML_PARAMS['MAIN']['LOAD_FLATS_FROM_FILE'] === 'Y'
                    && $this->CParser->isXmlExists( $XML_PARAMS['MAIN']['XML_LOCAL_FILE_FROM_CRM'] )
                    && !isset($params['onlyActiveStatus']) && $params['onlyActiveStatus'] !== true
                ){
                    $exelFlats = $this->helper->flats->getFlatsFromXmlFile( $XML_PARAMS['MAIN']['XML_LOCAL_FILE_FROM_CRM'] );

                    foreach ($exelFlats as $key => $_apartment):

                        if( $arApartmentGuid[ $_apartment['guid'] ] ) continue;

                        $_apartment['queue'] = $this->helper->flats->getFlatQueueByBuildingCode( $_apartment['info']['house'], $XML_PARAMS );
                        $_apartment['buildNumber'] = $_apartment['info']['house'];
                        $_apartment['section'] = $_apartment['info']['section'];
                        $_apartment['floor'] = $_apartment['info']['floor'];
                        $_apartment['numInPlatform'] = $_apartment['info']['pl'];

                        $building = array_flip( $XML_PARAMS['MAIN'][ 'BUILD_IMG_PATH' ] );

                        $plan = $this->getPlan( $_GET['obj'], $_apartment );

                        $planInfo = getPlanInfoByKey( getCNFkeyByGETkey( $_GET['obj'] ), $_apartment );

                        //$plan = $plan ? $plan : $this->helper->flats->getFlatPlan( getCNFkeyByGETkey( $_GET['obj'] ), $_apartment, $planInfo['format'], $planInfo['file_name'] );

                        $plan = ( $project === GET_HEADLINER || $project === GET_ILOVE
                            ?	$this->system->getSLink().'/'.DIR_UPLOAD_PLANS.$IMG_PATH.$this->helper->flats->getFlatPlan( $this->arCNF[ $project ], $_apartment )
                            :	$this->helper->flats->getFlatPlan( $this->arCNF[ $project ], $_apartment, $planInfo['format'], $planInfo['file_name'] ) );


                        $XML .= $obXmlType->getTag(
                            'apartment',
                            [
                                'guid' => $_apartment['guid'],
                                'building_id' => (int) $building[ $_apartment['buildNumber'] ] ? (int) $building[ $_apartment['buildNumber'] ] : $_apartment['info']['house'],
                                'quarter_number' => $arBuildList[ $_apartment['buildNumber'] ],
                                //'building_number' => 36,
                                'section_number' => $_apartment['section'],
                                'floor_number' => $_apartment['floor'],
                                'number' => $_apartment['info']['number'],
                                'square' => round( $_apartment['square'] ),
                                'room_count' => $_apartment['room_count'],
                                'total_cost' => explode( '.', $_apartment['total_cost'] )[0],
                                'cost_per_meter' => explode( '.', $_apartment['cost_per_meter'] )[0],
                                'status' => 0, //($this->helper->flats->getSimpleFlatStatusByID( $_apartment['status'] ) ? 1 : 0),
                                'crm_status' => 'n/a', //$_apartment['status'],
                                'plan' => $plan, //$this->getPlan( $_GET['obj'], $_apartment ),
                                'pl' => $_apartment['numInPlatform'],
                                'is_apartament' => 'N',

                            ]
                        );

                    endforeach;

                }


                $XML .= '</apartments>';

                if( $XML_BUILDING )
                {
                    $XML .= '<buildings>';
                    foreach ($XML_BUILDING as $XML_B)
                        $XML .= $XML_B;

                    $XML .= '</buildings>';
                }

                // echo '<pre>'.print_r($arXmlInfo,true).'</pre>';

                break;

            default:
                break;
        }

        //$XML .= $obXmlType->getXMLFooter([]);
        //echo $XML;
        //die();
        return $XML;
    }


    public function getInfoFromXMLFile( $project=false, $BuildingID=false, $xml=false, $params=[] )
    {
        if( !is_object( $xml )
            || !isset( $params['XML_PARAMS'] )
            || !isset( $project )
            || ( isset($params['listBuildingId']) && !in_array( $BuildingID,$params['listBuildingId'] ) )
        ) return false;


        $XML_PARAMS = $params['XML_PARAMS'];
        $IMG_PATH = $XML_PARAMS[ 'IMG_PATH' ];


        if( isset( $params['plans'] ) )
        {
            if( isset( $params['plans']['format'] ) && is_dir( $_SERVER['DOCUMENT_ROOT'].'/'.DIR_UPLOAD_PLANS.'/'.$_GET['obj'].'_'.$params['plans']['format'] )  )
                $IMG_PATH = '/'.$_GET['obj'].'_'.$params['plans']['format'].'/';
        }


        foreach ( $xml->developers->developer->projects->project->buildings->building as $building )
        {
            if( (int) $building->id !== $BuildingID ) continue;

            $_building = (array) $building;
            $_apartments = (array) $building->apartments;



            $XML_BUILDING[ $_building['id'] ] = [
                'maxPrice' => $_building['maxPrice'],
                'minPrice' => $_building['minPrice'],
                'id' => $_building['id'],
                'name' => $_building['name'],
                'adress' => $_building['adressBuild'],
                'status' => $_building['status'],
                'sectionCount' => ( $_building['sectionCount'] ? $_building['sectionCount'] : $XML_PARAMS['MAIN']['CORP_INFO'][ $_building['id'] ]['sectionCount'] ),
                'floorsCount' => ( $_building['floorsCount'] ? $_building['floorsCount'] : $XML_PARAMS['MAIN']['CORP_INFO'][ $_building['id'] ]['floors'] ),
                'deliveryPeriod' => $_building['deliveryPeriod'],
                'mountingBeginning' => $_building['mountingBeginning'],

                'countAll' => $_apartments['countAll'],
                'count' => [ $_apartments['count1'],$_apartments['count2'],$_apartments['count3'] ],
                'quantity' => [ $_apartments['quantity1'],$_apartments['quantity2'],$_apartments['quantity3'] ],
                'price' => [ $_apartments['price1'],$_apartments['price2'],$_apartments['price3'] ],

                'countTotal' => $_apartments['countTotal'],
            ];

            foreach ($building->apartments->apartment as $apartment)
            {
                $_apartment = (array) $apartment;

                if( isset($params['onlyActiveStatus']) && $params['onlyActiveStatus'] === true ) {
                    if ( !$this->getSimpleFlatStatusByID( $_apartment['status'] ) )
                        continue;
                }



                # придумать составной ключ keyType
                $filter_error = false;
                if( isset( $params['flats'] ) )
                {
                    if( $params['flats']['keyType'] === 'mixed' && count( $params['flats']['list'] ) )
                    {
                        $b_id = (int) $building->id;
                        if( !in_array( ($b_id.'_'.$_apartment['section'].'_'.$_apartment['floor'].'_'.$_apartment['numOrder']), $params['flats']['list'] ) )
                            $filter_error = true;
                    }

                    if( $params['flats']['keyType'] === 'if' && isset( $params['flats']['filter']) )
                    {
                        foreach ( $params['flats']['filter'] as $key => $val ):

                            if( isset( $_apartment[ $key ] ) )
                            {
                                foreach ( $val as $__key => $__val )
                                {
                                    if( $__key === '>' && (int) $_apartment[ $key ] < (int) $__val ) $filter_error = true;
                                    if( $__key === '<' && (int) $_apartment[ $key ] > (int) $__val ) $filter_error = true;
                                    if( $__key === '=' && (int) $_apartment[ $key ] !== (int) $__val ) $filter_error = true;
                                }
                            }

                        endforeach;
                    }
                }

                if( !$filter_error )
                {
                    //if( !isset( $params['ApartmentsGuiD'] ) )
                    //   $arApartmentGuid[ $_apartment['guid'] ] = $_apartment['guid'];

                    $_apartment['queue'] = $params['queue'] ? $params['queue'] : 1;
                    //$_apartment['queue'] = $this->helper->flats->getFlatQueueByBuildingCode( $_apartment['info']['house'], $XML_PARAMS );

                    //$_apartment['buildNumber'] = ( preg_replace("/[^0-9]/", '', (string) $building->buildNumber ));
                    $_apartment['buildNumber'] = ($XML_PARAMS['MAIN']['BUILD_IMG_PATH'] && $XML_PARAMS['MAIN']['BUILD_IMG_PATH'][$_building['id']]
                        ? $XML_PARAMS['MAIN']['BUILD_IMG_PATH'][$_building['id']]
                        : (preg_replace("/[^0-9]/", '', (string)$building->buildNumber)));


                    $arBuildList[$_apartment['buildNumber']] = $_apartment['queue'];

                    //$plan = $this->getPlan( $_GET['obj'], $_apartment );
                    //$plan = $plan ? $plan : $this->helper->flats->getFlatPlan( getCNFkeyByGETkey( $_GET['obj'] ), $_apartment, $planInfo['format'], $planInfo['file_name'] );


                    $planInfo = getPlanInfoByKey(getCNFkeyByGETkey($project), $_apartment);
                    $plan = ($project === GET_HEADLINER || $project === GET_ILOVE || $project === GET_BAUMANHOUSE
                        ? $this->system->getSLink() . '/' . DIR_UPLOAD_PLANS . $IMG_PATH . $this->getFlatPlan(getCNFkeyByGETkey($project), $_apartment, (isset($params['plans']['format']) ? $params['plans']['format'] : false))
                        : $this->getFlatPlan(getCNFkeyByGETkey($project), $_apartment, $planInfo['format'], $planInfo['file_name']));


                    $arApartment = [
                        'queue' => $_apartment['queue'],
                        'guid' => $_apartment['guid'],
                        'building_id' => (int)$building->id,
                        'quarter_number' => $_apartment['queue'],
                        //'building_number' => 36,
                        'section_number' => $_apartment['section'],
                        'floor_number' => $_apartment['floor'],
                        'number' => $_apartment['numOrder'],
                        'square' => $_apartment['squareCommon'],
                        'room_count' => $_apartment['rooms'],
                        'total_cost' => str_replace(',', '.', $_apartment['cost']),
                        'cost_per_meter' => $_apartment['squareMetrPrice'],
                        'status' => ($this->getSimpleFlatStatusByID($_apartment['status']) ? 1 : 0),
                        'crm_status' => $_apartment['status'],
                        'plan' => $plan, //$this->getPlan( $_GET['obj'], $_apartment ),
                        'pl' => $_apartment['numInPlatform'],
                        'is_apartament' => $project === GET_DOM128 ? 'Y' : 'N',
                        'buildNumber' => $_apartment['buildNumber'],
                        "type_finish" => $_apartment['typeFinish'],
                    ];

                    if (isset($params['discount'])) {
                        if (isset($params['discount']['all'])) {
                            $arApartment['old_total_cost'] = $arApartment['total_cost'];
                            $total_cost = (int)$arApartment['total_cost'];
                            $arApartment['total_cost'] = $total_cost - (($total_cost / 100) * ( int )$params['discount']['all']);
                            $arApartment['discount'] = ( int )$params['discount']['all'];

                            $arApartment['discount_cost'] = ($total_cost / 100) * ( int )$params['discount']['all'];

                        } elseif (count($params['discount']['list'])) {
                            if ($params['discount']['keyType'] === 'mixed') {
                                $b_id = (int)$building->id;
                                if (array_key_exists($b_id . '_' . $_apartment['section'] . '_' . $_apartment['floor'] . '_' . $_apartment['numOrder'], $params['discount']['list'])) {
                                    $arApartment['old_total_cost'] = $arApartment['total_cost'];
                                    $total_cost = (int)$arApartment['total_cost'];
                                    $arApartment['total_cost'] = $total_cost - (($total_cost / 100) * ( int )$params['discount']['list'][$b_id . '_' . $_apartment['section'] . '_' . $_apartment['floor'] . '_' . $_apartment['numOrder']]);
                                    $arApartment['discount'] = $params['discount']['list'][$b_id . '_' . $_apartment['section'] . '_' . $_apartment['floor'] . '_' . $_apartment['numOrder']];

                                    $arApartment['discount_cost'] = ($total_cost / 100) * ( int )$params['discount']['list'][$b_id . '_' . $_apartment['section'] . '_' . $_apartment['floor'] . '_' . $_apartment['numOrder']];
                                }
                            }
                        }
                    }

                    $arApartmentsList[$_apartment['guid']] = $arApartment;
                }
            }

            $XML_BUILDING[ $_building['id'] ]['apartments'] = $arApartmentsList;
        }

        return $XML_BUILDING;
    }
}