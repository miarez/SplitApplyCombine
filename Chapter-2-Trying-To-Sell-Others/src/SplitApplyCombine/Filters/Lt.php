<?php
namespace Chapter2\SplitApplyCombine\Filter;

class Lt extends Term
{

    public function validate(
        int $value
    ): bool
    {
        if ($value < $this->value) {
            return true;
        }
        return false;
    }
}