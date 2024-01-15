<?php
namespace Chapter2\SplitApplyCombine\Filter;
# NOT IN
class Nin extends Terms
{
    public function validate(
        string $value
    ): bool
    {
        if (in_array($value, $this->values)) {
            return false;
        }
        return true;
    }
}

