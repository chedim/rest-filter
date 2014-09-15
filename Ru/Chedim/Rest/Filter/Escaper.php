<?php

namespace Ru\Chedim\Rest\Filter;

interface Escaper {

    /**
     * Escapes field name to be inserted into query
     * @param $field
     * @return string
     */
    public function field($field);

    /**
     * Escapes field value to be inserted into query
     * @param $value
     * @return string
     */
    public function value($value);
}

?>