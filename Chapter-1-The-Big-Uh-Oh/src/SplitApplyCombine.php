<?php
namespace Chapter1;

class SplitApplyCombine {

    public function execute($data, $group_by, $aggregate, $showGroupedColumn = 1, $popOffStackBy = '')
    {
        $grouped = [];
        $this->distinct = [];
        $this->array = [];


        # you need a group by for this algorithm to work
        if (!$group_by) return "group_by required for operation";

        # if no aggregate function was passed in, just return a count of the rows
        if (!$aggregate) $aggregate[0] = ['count', '*', 'row_count'];


        $popOffStack = false;
        if ($popOffStackBy != '') {
            # grab my first client-name
            $currentSelector = $data[0][$popOffStackBy];
            $stack = [];
            $popOffStack = true;
        }


        # iterate over transactional data
        foreach ($data as $k => $row) {

            if ($popOffStack) {
                # if the current client-name does not match the initial client-name...
                if ($row[$popOffStackBy] != $currentSelector) {
                    # add the data to a separate array where the client-name is key
                    $stack = array_merge($stack, $grouped);
                    # clear my grouped array
                    unset($grouped);
                    $grouped = [];
                    # reset my "current" client-name
                    $currentSelector = $row[$popOffStackBy];
                }
            }


            # account for empty array elements
            if (!$row) continue;

            # each loop we build a new key based on the values of the grouped fields

            $key = self::getKey($row, $group_by);

            /**
             * Grouped Array Looks Like:
             * [key1] = [data]
             * [key2] = [data] ...
             * if [key3] does not yet exist, create it & append it to grouped array
             * It will exist the next loop, and go into the else statement instead
             */
            if (!array_key_exists($key, $grouped)) {

                # only show the value of the group-by in the column results if I request it
                if ($showGroupedColumn === 1) {
                    foreach ($group_by as $gkey) {
                        $grouped[$key][$gkey] = $row[$gkey];
                    }
                }

                # make this array entry if it does not exist yet
                $grouped = $this->initializeArray($grouped, $key, $row, $aggregate);


            } else {

                /*
                 * if [key3] does exist, grab the existing row & modify it with the additional data
                 */
                # if something exists in the array already with this key!
                # modify the existing array
                $grouped = $this->appendToArray($grouped, $key, $row, $aggregate);

            }

            # free up memory by removing the row I just processed
            unset($data[$k]);

        }


        if ($popOffStack) {
            $stack = array_merge($stack, $grouped);
            foreach ($stack as $key => $stackSubInfo) {
                if ($this->distinct[$key] != "") {
                    foreach ($this->distinct[$key] as $distinctKey => $distinctValue) {
                        $stack[$key][$distinctKey] = sizeof($distinctValue);
                    }
                }

                if ($this->array[$key] != "") {
                    foreach ($this->array[$key] as $distinctKey => $distinctArray) {
                        foreach ($distinctArray as $distinctValue => $distinctCount) {
                            $stack[$key][$distinctKey][] = $distinctValue;
                        }
                    }
                }
            }

            unset($grouped);
            return $stack;
        } else {

            # this little section is here to make "distinct count" possible!
            if ($this->distinct) {
                foreach ($grouped as $key => $value) {
                    if ($this->distinct[$key]) {
                        foreach ($this->distinct[$key] as $type => $uniqueArray) {
                            $grouped[$key][$type] = sizeof($uniqueArray);
                        }
                    }
                }
            }

        }

        return $grouped;
    }


    /**
     * Part of SplitApplyCombine Algorithm
     * @param $row
     * @param $group_by
     * @return string
     */
    private static function getKey($row, $group_by)
    {
        $key = "";

        # glue for appending group by values
        $keyGlue = "|||";

        # loop through each group by
        foreach ($group_by as $g) {

            # check if there is a value in the row for the key of the current grouper
            $currRow = ($row[$g]) ?? 'null';

            # create key out of the current row value with glue
            $key .= $currRow . $keyGlue;
        }
        # trim remaining glue
        $key = rtrim($key, $keyGlue);

        # return key to use for grouping
        return $key;
    }

    /**
     * Part of SplitApplyCombine Algorithm
     * @param $grouped
     * @param $key
     * @param $row
     * @param $aggregateArray
     * @return mixed
     */
    private function appendToArray($grouped, $key, $row, $aggregateArray)
    {


        // iterate over all the aggregations I passed in (all the groups by)
        foreach ($aggregateArray as $aggregateSubArray) {

            $aggregate = $this->parseAggregateArray($aggregateSubArray);
            $applyAggregation = $this->testCondtional($aggregate['conditionals'], $row);

            # Need to check as all fields are optional in elastic
            # Also check whether this should be appended to the array in the first place
            if (array_key_exists($aggregate['groupByKey'], $row) && $applyAggregation === true) {

                # if I would like to divide everything by 100!
                if (!$aggregate['script_divide']) {
                    $appendValue = $row[$aggregate['groupByKey']];
                } else {
                    $appendValue = $row[$aggregate['groupByKey']] / 100;
                }


                // Two cases, summation and taking last value, more can be added here
                if ($aggregate['aggFunction'] == "sum") {

                    $grouped[$key][$aggregate['alias']] += $appendValue;

                } elseif ($aggregate['aggFunction'] == "count") {

                    $grouped[$key][$aggregate['alias']] += 1;

                } elseif ($aggregate['aggFunction'] == "distinct") {

                    $this->distinct[$key][$aggregate['alias']][$row[$aggregate['groupByKey']]] = 1;

                } elseif ($aggregate['aggFunction'] == "array") {

                    $this->array[$key][$aggregate['alias']][$row[$aggregate['groupByKey']]] = 1;


                } elseif ($aggregate['aggFunction'] == "last") {

                    $grouped[$key][$aggregate['alias']] = $appendValue;

                } elseif ($aggregate['aggFunction'] == "avg") {

                    $grouped[$key]["aggSUM_" . $aggregate['alias']] += $appendValue;
                    $grouped[$key]["aggCOUNT_" . $aggregate['alias']] += 1;
                    $grouped[$key][$aggregate['alias']] = number_format($grouped[$key]["aggSUM_" . $aggregate['alias']] / $grouped[$key]["aggCOUNT_" . $aggregate['alias']], 2);

                }
            }


        }


        return $grouped;
    }

    /**
     * Part of SplitApplyCombine Algorithm
     * @param $grouped
     * @param $key
     * @param $row
     * @param $aggregateArray
     * @return mixed
     */
    private function initializeArray($grouped, $key, $row, $aggregateArray)
    {


        //        pp($row, 1);
        // Store key fields for display
        # if I want to add the variables I grouped by to the actual array, add the following code
        # comment this code if you only want to see the aggregate function results in the array

        // iterate over all the aggregations I passed in (all the groups by)
        foreach ($aggregateArray as $aggregateSubArray) {

            $aggregate = $this->parseAggregateArray($aggregateSubArray);

            if (array_key_exists($aggregate['groupByKey'], $row)) {

                # We need to check if the row has any special conditions attached
                $applyAggregation = $this->testCondtional($aggregate['conditionals'], $row);

                # Do my row values match the passed in conditionals?
                # if yes, count the values, if not, move on!
                if ($applyAggregation === true) {

                    # if the aggFunction is to just count, initialize the first entry and move on
                    if ($aggregate['aggFunction'] == 'count') {
                        $grouped[$key][$aggregate['alias']] = 1;
                    } elseif ($aggregate['aggFunction'] == 'distinct') {
                        $this->distinct[$key][$aggregate['alias']][$row[$aggregate['groupByKey']]] = 1;

                    } elseif ($aggregate['aggFunction'] == 'array') {
                        $this->array[$key][$aggregate['alias']][$row[$aggregate['groupByKey']]] = 1;


                    } else {

                        # for all other cases we're going to need to store the actual value for initilization
                        $insertValue = $row[$aggregate['groupByKey']];


                        if ($aggregate['aggFunction'] == 'avg') {
                            $grouped[$key]["aggSUM_" . $aggregate['alias']] = $insertValue;
                            $grouped[$key]["aggCOUNT_" . $aggregate['alias']] = 1;
                        }

                        if (!$insertValue) $insertValue = 0;
                        $grouped[$key][$aggregate['alias']] = $insertValue;


                    }
                }
            }
        }
        return $grouped;
    }

    /**
     * Part of SplitApplyCombine Algorithm
     * Test Row Against Conditional
     * Determines whether row values should be appended to the array
     * Currently Supports: [=, <>, >, <, >=, <=, contains, starts_with, ends_with];
     * To do: [distinct]
     * @param $aggregate array of aggregate functions passed in at the start
     * @param $row array of current data in row array
     * @return bool
     */
    private function testCondtional($aggregate, $row)
    {

        # if there is nothing to test --> tell script it passed the conditional
        if (!$aggregate) {
            return true;
        }

        foreach ($aggregate as $k => $v) {

            if ($v['operator'] === '=') {

                if ($row[$v['field']] !== $v['value']) {
                    return false;
                }

            } elseif ($v['operator'] === '<>') {

                if ($row[$v['field']] == $v['value']) {
                    return false;
                }

            } elseif ($v['operator'] === '>') {

                if ($row[$v['field']] <= $v['value']) {
                    return false;
                }

            } elseif ($v['operator'] === '<') {

                if ($row[$v['field']] >= $v['value']) {
                    return false;
                }

            } elseif ($v['operator'] === '>=') {

                if ($row[$v['field']] < $v['value']) {
                    return false;
                }

            } elseif ($v['operator'] === '<=') {

                if ($row[$v['field']] > $v['value']) {
                    return false;
                }

            } elseif ($v['operator'] === 'contains') {

                if (!preg_match("/{$v['value']}/", $row[$v['field']])) {
                    return false;
                }

            } elseif ($v['operator'] === 'starts_with') {

                if (!preg_match("/^{$v['value']}/", $row[$v['field']])) {
                    return false;
                }

            } elseif ($v['operator'] === 'ends_with') {

                if (!preg_match("/{$v['value']}$/", $row[$v['field']])) {
                    return false;
                }

            } elseif ($v['operator'] === 'isNull') {

                if (!empty($row[$v['field']])) {
                    return false;
                }

            } elseif ($v['operator'] === 'isNotNull') {

                if (empty($row[$v['field']])) {
                    return false;
                }

            }

        }

        # if I made it all the way down here, it means nothing was false... return true!!
        return true;
    }

    /**
     * Part of SplitApplyCombine Algorithm
     * @param $aggregate
     * @return mixed
     */
    private function parseAggregateArray($aggregate)
    {

        $result['aggFunction'] = $aggregate[0];


        # allow support for passing in *
        if ($aggregate[1] == '*') {
            # use the _id row, since every result should have an _id for algorithm
            $aggregate[1] = '_id';
        }

        #
        $result['groupByKey'] = $aggregate[1];

        if ($aggregate[2]) {
            $result['alias'] = $aggregate[2];
        } else {
            $result['alias'] = "{$result['aggFunction']}({$result['groupByKey']})";
        }

        # if conditionals were passed into the array, grab them & append them to result
        if ($aggregate[3]) {
            # user can pass in multiple conditionals --> make sure they are all accounted for
            foreach ($aggregate[3] as $k => $v) {
                $conditional = [];
                $conditional['field'] = $v[0];
                $conditional['operator'] = $v[1];
                $conditional['value'] = $v[2];
                $conditionals[] = $conditional;
            }
            # append it to the resulting array
            $result['conditionals'] = $conditionals;
        }

        if ($aggregate[4]) {
            $result['script_divide'] = 1;
        }
        if ($aggregate[4]) {
            $result['format'] = 1;
        }

        return $result;

    }

}