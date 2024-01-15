<?php
namespace Chapter2\SplitApplyCombine\Filter;

class Gt extends Term
{

    public function validate(
        int $value
    ): bool
    {
        if ($value > $this->value) {
            return true;
        }
        return false;
    }
}