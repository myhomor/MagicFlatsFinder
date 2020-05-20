<?
namespace MagicFlatsFinder\base;

class Fields extends BaseObject
{
    const DEF_TMP = 'feeds';
    public $apartment;
    public $building;

    protected $fields_map = ['apartment','building'];
    protected $result_fields_map = [];


    /**
     *
     */
    public function init()
    {
        if( isset($this->config['fields_tmp']))
        {
           if( is_string( $this->config['fields_tmp'] ) )
           {
               foreach ($this->fields_map as $item) {
                   $name = self::getMethodName( $this->config['fields_tmp'],$item );

                   if( $this->hasMethod( $name ) )
                       $this->result_fields_map[ $item ] = $name;
                   else
                       $this->result_fields_map[ $item ] = self::getMethodName( self::DEF_TMP, $item );
               }
           }
           if( is_array( $this->config['fields_tmp'] ) )
           {
               foreach ($this->fields_map as $item) {
                   $u_type = isset( $this->config['fields_tmp'][ $item ] ) ? $this->config['fields_tmp'][ $item ] : self::DEF_TMP;

                   $name = self::getMethodName( $u_type,$item );

                   if( $this->hasMethod( $name ) )
                       $this->result_fields_map[ $item ] = $name;
                   else
                       $this->result_fields_map[ $item ] = self::getMethodName( self::DEF_TMP, $item );
               }
           }
        }else{
            foreach ($this->fields_map as $item)
                $this->result_fields_map[ $item ] = self::getMethodName( self::DEF_TMP, $item );
        }

        $this->getFields();
    }


    /**
     * @param string $type
     */
    public function getFields($type = 'all')
    {
        if( $type === 'all' ) {
            foreach ($this->result_fields_map as $key => $method)
            {
                if( $this->hasMethod( $method ) )
                    $this->{ $key } = $this->{ $method }();
            }
        }

        if( isset( $type, $this->result_fields_map ) ) {
            if( $this->hasMethod( $this->result_fields_map[ $type ] ) )
                $this->{ $type } = $this->{ $this->result_fields_map[ $type ] }();
        }
    }

    protected static function getMethodName( $type, $block )
    {
        return '_'.$type.'_'.$block;
    }

    protected function _feeds_apartment()
    {
        //field name from crm => field name to tmp
        return [
            'id' => 'id',
            'queue' => 'queue',
            'guid' => 'guid',
            'building_id' => 'building_id',
            'section' => 'section_number',
            'floor' => 'floor_number',
            'numOrder' => 'number',
            'squareCommon' => 'square',
            'rooms' => 'room_count',
            'cost' => 'total_cost',
            'squareMetrPrice' => 'cost_per_meter',
            'status' => 'status',
            'crm_status' => 'crm_status',
            'plan' => 'plan',
            'numInPlatform' => 'pl',
            'is_apartament' => 'is_apartament',
            'buildNumber' => 'buildNumber',
            "typeFinish" => 'type_finish',
            "oral_reserv" => 'oral_reserv',
        ];
    }

    protected function _feeds_building()
    {
        return [

            //field name from crm => field name to tmp

            'price' => 'price',
            'id' => 'id',
            'name' => 'name',
            'adressBuild' => 'address',
            'status' => 'status',
            'sectionCount' => 'sectionCount',
            'floorsCount' => 'floorsCount',
            'deliveryPeriod' => 'deliveryPeriod',
            'mountingBeginning' => 'mountingBeginning',
            'countAll' => 'countFree',
            'count_' => 'count',
            'quantity_' => 'quantity',
            'price_' => 'flatsCost',
            'countTotal' => 'countTotal',
        ];
    }
}