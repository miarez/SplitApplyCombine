<?php
namespace Chapter2\SplitApplyCombine\Filter;


class Neq extends Term {

    public function validate(
        $value
    ) : bool
    {
        if($value == $this->value){
            return false;
        }
        return true;
    }
}