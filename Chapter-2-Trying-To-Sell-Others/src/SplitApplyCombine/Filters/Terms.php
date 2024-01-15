<?php
namespace Chapter2\SplitApplyCombine\Filter;

abstract class Terms extends Filter {

    public function __construct(
        string $field,
        array $values
    )
    {
        $this->field    = $field;
        $this->values    = $values;
    }
}

