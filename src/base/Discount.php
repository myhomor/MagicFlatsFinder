<?php
namespace MagicFlatsFinder\base;

class Discount extends BaseObject
{
    const KEY_MIX = 'mixed_key';
    const KEY_ID = 'id';
    const KEY_GUID = 'guid';
    const DEF_KEY = self::KEY_MIX;
    const PARAM_TOTAL_COST = 'total_cost';

    protected $discount_all=false;
    protected $discount_select=false;
    protected $key_select=false;
    protected $list;

    public $debug;

    public function init()
    {
        if( isset( $this->config['all'] ) )
            $this->discount_all = (int) $this->config['all'];
        if( isset( $this->config['select'] ) && isset( $this->config['select']['list'] ) && isset( $this->config['select']['key'] ) )
        {
            $this->key_select = $this->config['select']['key'];

            if( isset( $this->config['select']['discount'] ) )
                $this->discount_select = $this->config['select']['discount'];

            foreach ($this->config['select']['list'] as $item)
            {
                if( strripos($item, ':') === false)
                    $this->list[ $item ] = $this->discount_select ? (int) $this->discount_select : (int) $this->discount_all;
                else
                    $this->list[ explode(":", $item )[0] ] = (int) explode(":", $item )[1];
            }
        }

        $this->debug['list'] = $this->list;
    }

    public function setDiscount( $element )
    {
        if( !is_array( $element ) ) return false;
        $status_list = false;
        $e_key = false;
        if( $this->key_select && is_array( $this->list ) && count( $this->list ) )
        {
            switch ($this->key_select )
            {
                //допилить передачу скидки
                case self::KEY_MIX:
                    if( key_exists( Helper::getFlatMixKey($element), $this->list ) &&  $this->list[ Helper::getFlatMixKey( $element ) ] !== 0 ) {
                        $e_key = Helper::getFlatMixKey($element);
                        $status_list = true;
                    }
                        break;
                case self::KEY_ID:
                case self::KEY_GUID:
                    $status_list = key_exists( $element[ $this->key_select ], $this->list ) && $this->list[ $element[ $this->key_select ] ] !== 0 ? true : false;
                    $e_key = $element[ $this->key_select ];
                    break;
            }

        }
        if( $status_list && $e_key ){
            $res = $this->_discount( $element, $this->list[ $e_key ] );
        }
        elseif( $this->discount_all ) {
            $res = $this->_discount( $element, $this->discount_all );
        }

        return $res;
    }

    protected function _discount( $element, $discount_val  )
    {
        if( !$discount_val ) return false;

        $res[ 'old_'.self::PARAM_TOTAL_COST ] = $element[ self::PARAM_TOTAL_COST ];
        $total_cost = (int) $element[ self::PARAM_TOTAL_COST ];
        $res[ self::PARAM_TOTAL_COST ] = $total_cost - ( ($total_cost / 100) * $discount_val );
        $res['discount'] = $discount_val;
        $res['discount_cost'] = ($total_cost / 100) * $discount_val;

        return $res;
    }
}