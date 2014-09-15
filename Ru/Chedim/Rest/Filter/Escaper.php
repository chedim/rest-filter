<?php

namespace Ru\Chedim\Rest\Filter;

class Escaper {

    private $core;

    public function __construct($args, $core) {
        $this->core = $core;
    }

    /**
     * Escapes field name to be inserted into query
     * @param $field
     * @return mixed
     */
    public function field($field){
        return $this->core->helpers['db']->column($field);
    }

    /**
     * Escapes field value to be inserted into query
     * @param $value
     * @return mixed
     */
    public function value($value){
        return $this->core->helpers['db']->value($value);
    }
}

?>