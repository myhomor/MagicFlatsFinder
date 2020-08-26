<?php
namespace MagicFlatsFinder\base;

class SimpleClass extends BaseObject
{
    protected $fields;
    protected $helper;

    final public function customizer()
    {
        $this->fields = new Fields( ( isset($this->config['fields_tmp']) ? $this->config['fields_tmp'] : [] ) );

        $this->helper = new Helper([
            'project' => $this->config['project']
        ]);
        $this->helper->_fields_apartment = $this->fields->apartment;
        $this->helper->_fields_building = $this->fields->building;

    }
}