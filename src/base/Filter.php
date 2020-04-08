<?php
namespace MagicFlatsFinder\base;


class Filter extends SimpleClass
{
    const LOGIC_AND = 'AND';
    const LOGIC_OR = 'OR';
    const LOGIC_DEF = self::LOGIC_AND;

    public $debug = [];


    public function check( $type, $_apartment, $filter  )
    {
        $this->debug = [];
        if( !is_string( $type ) )
            return false;

        if( !$this->hasMethod('_'.$type ) )
            return false;

        return $this->{'_'.$type}( $_apartment, $filter );
    }

    protected function _flat( $_apartment, $filter )
    {
        $logic = false;

        foreach ($filter as $ftype => $arFilter) {

            if( $ftype === 'logic' ){
                $logic = !isset( $filter[ $ftype ] ) || !in_array( $filter[ $ftype ], [ self::LOGIC_AND, self::LOGIC_OR ] ) ? self::LOGIC_DEF : $filter[ $ftype ];
                continue;
            }

            if( $ftype === 'by' ){
                $status[] = $this->debug['main']['by'] = $this->_filterBY( $_apartment, $arFilter ) ? 'Y' : 'N';
            }
            else{
                $status[] = $this->debug['main']['if'] = $this->_filterIF( $_apartment, $arFilter ) ? 'Y' : 'N';
            }

        }

        $logic = !$logic ? self::LOGIC_DEF : $logic;

        if( $logic == self::LOGIC_AND )
            $f_status = in_array('N', $status ) ? false : true;
        else
            $f_status = in_array('Y', $status ) ? true : false;

        $this->debug['main'] = array_merge( ['logic' => $logic, 'final_status' => ($f_status?'Y':'N') ], $this->debug['main'] );

        return $f_status;
    }


    protected function _filterBY( $element, $filter )
    {
        $status = [];
        foreach ( $filter as $type => $list )
        {
            if( $type === 'mixed_key' )
                $_key = $this->helper->getFlatMixKey( $element );

            if( $type === 'id' )
                $_key = $element[ $this->fields->apartment['id'] ];

            if( $type === 'guid' )
                $_key = $element[ $this->fields->apartment['guid'] ];

            $_status = ( !in_array( $_key, $list ) ) ? 'N' : 'Y';

            $this->debug['status_by']['mixed_key'][ $_key ] = $_status;
            $status[] = $_status;

        }

        return in_array('Y', $status ) ? true : false;
    }

    protected function _filterIF( $element, $filter )
    {
        $logic = key_exists( 'logic', $filter ) ? $filter['logic'] : self::LOGIC_DEF;

        if ( !in_array( $logic, [self::LOGIC_AND,self::LOGIC_OR] ) )
            return false;

        $this->debug['status_if']['logic'] = $logic;
        //$this->debug['element'] = $element;
        //$this->debug['filter'] = $filter;


        foreach ($filter as $field => $_filter)
        {
            if( in_array( $field, ['logic'] ) ) continue;

            if( count( $_filter ) == 1 ) {
                $status[ $field ] = $this->_logic($element, $field, $_filter) ? 'Y' : 'N';
                $this->debug['status_if'][$field] = [ 'flag' => $status[ $field ] ];
            }
            else
            {
                $_logic = key_exists( 'logic', $_filter ) ? $_filter['logic'] : self::LOGIC_DEF;

                foreach ($_filter as $_fkey => $_FFilter) {
                    if( $_fkey === 'logic' ) continue;

                    foreach ($_FFilter as $key => $val) {
                        $_status[$field][] = $this->_logic($element, $field, [$key => $val]) ? 'Y' : 'N';
                    }

                }

                if( $_logic == self::LOGIC_AND )
                    $status[ $field ] = in_array('N', $_status[ $field ] ) ? 'N' : 'Y';
                else
                    $status[ $field ] = in_array('Y', $_status[ $field ] ) ? 'Y' : 'N';

                $this->debug['status_if'][$field] = [ 'logic'=>$_logic, 'flag' => $status[ $field ], 'flags' => $_status[ $field ] ];
            }
            $arFlags[ $field ] = $status[ $field ];
        }

        if( $logic == self::LOGIC_AND )
            $FStatus = in_array('N', $arFlags ) ? 'N' : 'Y';
        else
            $FStatus = in_array('Y', $arFlags ) ? 'Y' : 'N';
        $this->debug['status_if']['logic_final'] = $FStatus;

        return $FStatus === 'Y' ? true : false;

    }

    protected function _logic( $element, $field, $_filter )
    {
        foreach ($_filter as $__key => $__val) {
                if ($__key === '>' && ( (int)$element[$field] < (int)$__val || (int)$element[$field] === (int)$__val ) ) return false;
                if ($__key === '<' && ( (int)$element[$field] > (int)$__val || (int)$element[$field] === (int)$__val ) ) return false;

                if ($__key === '>=' && (int)$element[$field] < (int)$__val ) return false;
                if ($__key === '<=' && (int)$element[$field] > (int)$__val ) return false;

                if ($__key === '=' && (int)$element[$field] !== (int)$__val) return false;
                if ($__key === '!=' && (int)$element[$field] === (int)$__val) return false;
        }

        return true;
    }
}