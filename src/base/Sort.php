<?php
namespace MagicFlatsFinder\base;


class Sort extends SimpleClass
{
    public $type = 'ASC';
    public $by;
    public $el_key = 'guid';

    protected $sort_list;
    protected $separator=false;
    protected $decimals=2;

    public function init()
    {
        if( $this->config )
        {
            $this->by = $this->config['by'];
            $this->type = isset($this->config['type']) ? mb_strtoupper( $this->config['type'] ) : $this->type;
            $this->el_key = isset($this->config['key']) ? $this->config['key'] : $this->el_key;
            $this->separator = isset( $this->config['separator'] ) ? $this->config['separator'] : $this->separator;
        }
    }

    public function addElementToSort($element)
    {
        if ($this->separator) {
            $key = explode($this->separator, $element[$this->by]);
            $this->sort_list[ $key[0] ][ $key[1] ][] = $element[ $this->el_key ];
        } else
            $this->sort_list[ (int) $element[ $this->by ] ][] = $element[ $this->el_key ];
    }

    public function sort()
    {
        if( $this->type === 'ASC' ) ksort( $this->sort_list );
        else krsort( $this->sort_list );

        $res = false;

        foreach ($this->sort_list as $arElements) {

            if( $this->separator ) {

                if( $this->type === 'ASC' ) ksort( $arElements );
                else krsort( $arElements );

                foreach ($arElements as $k => $ar_key_element) {
                    foreach($ar_key_element as $key_element) {
                        $res[] = $key_element;
                    }
                }

            }else{

                foreach ($arElements as $key_element) {
                    $res[] = $key_element;
                }

            }
        }

        return $res;
    }
}