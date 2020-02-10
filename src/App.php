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
        if (isset($this->config['xml_type']))
            $this->xml_type = $this->config['xml_type'];

        $this->helper = new base\Helper([
            'project' => $this->config['project'],
            'map' => 'http://feeds.dev.kortros.ru'
        ]);
    }

    public function find($house_id, $params)
    {
        if ($this->config['project'] === self::PROJECT_HEADLINER && $this->xml_type !== self::DEF_XML_TYPE)
            $xml = new base\SimpleXmlExtended(base\Parser::getXmlByUrl($this->config['xml']));
        else
            $xml = new base\SimpleXmlExtended(base\Parser::getXmlByUrl($this->config['xml'] . '?BuildingID=' . $house_id . '&deep=' . $this->deep));


        return $this->_find($house_id, $params, $xml);
    }

    protected function _find($house_id, $params, $xml)
    {
        foreach ($xml->developers->developer->projects->project->buildings->building as $building) {
            if ((int)$building->id !== $house_id) continue;

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
                'count' => [1 => $_apartments['count1'],
                    2 => $_apartments['count2'],
                    3 => $_apartments['count3'],
                    4 => $_apartments['count4'],
                    5 => $_apartments['count5'],
                ],

                'quantity' => [1 => $_apartments['quantity1'],
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

                if (isset($params['active']) && $params['active'] === true) {
                    if (!$this->helper->simpleFlatStatus($_apartment['status'], 'free'))
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

                    $_apartment['queue'] = $params['queue'] ? $params['queue'] : $this->queue;

                    $_apartment['buildNumber'] = (isset($params['build']['number'])
                        ? $params['build']['number']
                        : (preg_replace("/[^0-9]/", '', (string)$building->buildNumber)));


                    $arApartment = [
                        'queue' => $_apartment['queue'],
                        'guid' => $_apartment['guid'],
                        'building_id' => (int)$building->id,
                        'section_number' => $_apartment['section'],
                        'floor_number' => $_apartment['floor'],
                        'number' => $_apartment['numOrder'],
                        'square' => $_apartment['squareCommon'],
                        'room_count' => $_apartment['rooms'],
                        'total_cost' => str_replace(',', '.', $_apartment['cost']),
                        'cost_per_meter' => $_apartment['squareMetrPrice'],
                        'status' => ($this->helper->simpleFlatStatus($_apartment['status'], 'free') ? 1 : 0),
                        'crm_status' => $_apartment['status'],

                        'plan' => $this->helper->getFlatPlan($_apartment['guid'],
                            (int)$building->id,
                            $this->config['project'],
                            (isset($params['plans']['format']) ? $params['plans']['format'] : false)),

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

                    $res['flats'][$_apartment['guid']] = $arApartment;
                }
            }
        }
        if (!isset($params['select']))
            return $res;

        foreach ($params['select'] as $type) {
            if (isset($res[$type])) {
                $arRes[$type] = $res[$type];
            }
        }

        return count($params['select']) == 1 ? $arRes[$type] : $arRes;
    }
}