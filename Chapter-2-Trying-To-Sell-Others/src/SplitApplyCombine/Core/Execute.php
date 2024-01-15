<?php

namespace Chapter2\SplitApplyCombine\Core;
use Chapter2\SplitApplyCombine\Aggregation\BucketAggregation;
use Chapter2\SplitApplyCombine\Aggregation\MetricAggregation;

class Execute {

    public function __construct(
        array $dataFrame,
        Builder $query
    )
    {
        $this->dataFrame    = $dataFrame;
        $this->query        = $query;
        $this->result       = [];

        $this->keyDeliminator = "|";

        $this->dataPointer          = 0;
        $this->aggregationPointer   = NULL;
    }

    public function calculate() : array
    {
        $meta = [];

        foreach($this->dataFrame as $rowIndex=>$row)
        {
            # no reason to do anything on an empty row
            if(empty($row))
            {
                continue;
            }


            $row = $this->filterColumns($this->query->selectedColumns, $row);


            # validate my filters if any are present
            if($this->validateFilters((array) $this->query->filters, $row) == FALSE) continue;

            # if no aggregations are set, we are honestly only applying the filter
            if(empty((array) $this->query->aggregations))
            {
                $this->result[$rowIndex] = $row;
                continue;
            }

            $this->aggregationPointer = &$this->query->aggregations;
            $this->processAggregations($row);
        }

        unset($meta);
        return $this->result;
    }

    private function filterColumns(
        array $columnSelectors,
        array $row
    )
    {
        if(empty($columnSelectors)) return $row;

        $filteredRow = [];
        foreach($columnSelectors as $columnSelector)
        {
            $filteredRow[$columnSelector] = $row[$columnSelector];
        }
        return $filteredRow;
    }

    private function processAggregations(
        $row
    )
    {
        foreach($this->aggregationPointer as $aggregation)
        {
            # validate my filters
            if($this->validateFilters((array) $aggregation->filters, (array) $row) == FALSE) continue;

            if($aggregation instanceof MetricAggregation)
            {
                $this->dataPointer = &$this->result[$aggregation->alias];
                $this->processMetricAggregation($aggregation, $row);
                continue;
            }

            # build the unique key
            $key = $this->buildRowKey($row, $aggregation);

            # loop over the nested metric aggregations bound to the base bucket aggregation
            foreach((array) $aggregation->aggregations as $subAggregation)
            {
                if($subAggregation instanceof BucketAggregation)
                {
                    Throw new \TypeError("Nested Bucket Aggregations Not Supported In This Version");
                }

                foreach($aggregation->fields as $field)
                {
                    $this->result[$aggregation->alias][$key][$field] = $row[$field];
                }

                $this->dataPointer = &$this->result[$aggregation->alias][$key][$subAggregation->alias];
                $this->processMetricAggregation($subAggregation, $row, $aggregation->alias, $key);
            }

        }
    }

    private function processMetricAggregation(
        $metricAggregation,
        $row
    )
    {
        $className  = strtoupper((new \ReflectionClass($metricAggregation))->getShortName());
        $value      = $row[$metricAggregation->field];
        if($metricAggregation->expression){
            $value = $metricAggregation->expression->express($value);
        }
        
        switch ($className){
            case "COUNT":
                $this->dataPointer += 1;
                break;
            case "SUM":
                $this->dataPointer += $value;
                break;
            case "AVG":
                $this->dataPointer[] = $value;
                break;
            case "DISTINCT":
                $this->dataPointer[$value] += 1;
                break;
            case "LAST":
                $this->dataPointer = $value;
                break;
            default:
                # count is the default
                $this->dataPointer += 1;
                break;
        }
    }

    private function validateFilters(
        array $filters,
        array $row
    ) : bool
    {
        foreach($filters as $filter)
        {
            if($filter->validate((string) $row[$filter->field]) === FALSE){
                return FALSE;
            }
        }
        return TRUE;
    }


    private function buildRowKey(
        array $row,
        Aggregation\Aggregation $aggregation
    ) : string
    {

        $aggregationType = (new \ReflectionClass($aggregation))->getShortName();

        $key = "";
        # loop through each group by
        foreach($aggregation->fields as $g)
        {
            # check if there is a value in the row for the key of the current grouper
            switch ($aggregationType){
                case "DateHistogram":
                    switch ($aggregation->format){
                        case "MONTH":
                            $currRow = (substr($row[$g], 0, 8)."01") ?? 'null';
                            break;
                        case "DAY":
                            $currRow = (substr($row[$g], 0, 10)) ?? 'null';
                            break;
                    }

                    break;
                default:
                    $currRow = ($row[$g]) ?? 'null';
                    break;
            }

            # create key out of the current row value with glue
            $key .= $currRow.$this->keyDeliminator;
        }
        # trim remaining glue
        $key = rtrim($key, $this->keyDeliminator);
        # return key to use for grouping
        return $key;
    }



}