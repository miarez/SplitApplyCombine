<?php
namespace Chapter2\SplitApplyCombine\Filter;

abstract class Term extends Filter {

    public function __construct(
        string $field,
        string $value
    )
    {
        $this->field    = $field;
        $this->value    = $value;
    }
}

