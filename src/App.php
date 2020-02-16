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


    protected $helper;
    protected $xml_type = self::DEF_XML_TYPE;
    protected $filter;
    protected $discount;
    protected $fields;
    protected $debug = false;

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
        if ($this->config['project'] === self::PROJECT_HEADLINER && $this->xml_type !== self::DEF_XML_TYPE)
            $xml = new base\SimpleXmlExtended(base\Parser::getXmlByUrl($this->config['xml']));
        else
            $xml = new base\SimpleXmlExtended(base\Parser::getXmlByUrl($this->config['xml'] . '?BuildingID=' . $house_id . '&deep=' . $this->deep));

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
                $arNames['count_'] => [1 => $_apartments['count1'],
                    2 => $_apartments['count2'],
                    3 => $_apartments['count3'],
                    4 => $_apartments['count4'],
                    5 => $_apartments['count5'],
                ],

                $arNames['quantity_'] => [1 => $_apartments['quantity1'],
                    2 => $_apartments['quantity2'],
                    3 => $_apartments['quantity3'],
                    4 => $_apartments['quantity4'],
                    5 => $_apartments['quantity5'],
                ],

                $arNames['price_'] => [1 => $_apartments['price1'],
                    2 => $_apartments['price2'],
                    3 => $_apartments['price3'],
                    4 => $_apartments['price4'],
                    5 => $_apartments['price5'],
                ],

                $arNames['countTotal'] => $_apartments['countTotal'],
            ];


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
                    $arNames['squareCommon'] => $_apartment['squareCommon'],
                    $arNames['rooms'] => $_apartment['rooms'],
                    $arNames['cost'] => str_replace(',', '.', $_apartment['cost']),
                    $arNames['squareMetrPrice'] => $_apartment['squareMetrPrice'],
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

                if( ( isset( $params['filter'] ) && count( $params['filter'] ) && $this->filter->check( 'flat', $arApartment, $params['filter'] ) ) ){

                    if( $this->debug )
                            $arApartment['debug'] = $this->filter->debug;

                    $res['flats'][$_apartment['guid']] = $arApartment;
                }
                if( !isset( $params['filter'] ) && !count( $params['filter'] ) )
                    $res['flats'][$_apartment['guid']] = $arApartment;
            }
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
}