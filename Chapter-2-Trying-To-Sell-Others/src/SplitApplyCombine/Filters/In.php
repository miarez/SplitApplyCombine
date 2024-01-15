<?php
namespace Chapter2\SplitApplyCombine\Filter;

class In extends Terms
{
    public function validate(
        string $value
    ): bool
    {
        if (in_array($value, $this->values)) {
            return true;
        }
        return false;
    }
}