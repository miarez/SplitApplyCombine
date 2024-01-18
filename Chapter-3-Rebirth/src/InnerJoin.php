<?php


/**
 * Doesn't handle outer/full/right/etc.
 * Hasn't been fully tested
 */
class InnerJoin
{
    public static function nestedLoopJoin(
        $table_a,
        $table_b,
        array $keys,
        $table_a_name = 'table_a',
        $table_b_name = 'table_b'
    ): array
    {
        $out = [];
        foreach ($table_a as $table_a_record) {
            // Prepend table name to each key in the record from table A
            $prefixedTableARecord = [];
            foreach ($table_a_record as $key => $value) {
                $prefixedTableARecord[$table_a_name . '_' . $key] = $value;
            }

            foreach ($table_b as $table_b_record) {
                // Prepend table name to each key in the record from table B
                $prefixedTableBRecord = [];
                foreach ($table_b_record as $key => $value) {
                    $prefixedTableBRecord[$table_b_name . '_' . $key] = $value;
                }

                $allMatch = true;
                // Check if the values match for each key in both records
                foreach ($keys as $key) {
                    if (!isset($table_a_record[$key]) || !isset($table_b_record[$key]) ||
                        $table_a_record[$key] !== $table_b_record[$key]) {
                        $allMatch = false;
                        break; // No need to check further if any key doesn't match
                    }
                }
                // Merge records if all keys match
                if ($allMatch) {
                    $out[] = array_merge($prefixedTableARecord, $prefixedTableBRecord);
                }
            }
        }
        return $out;
    }


    public static function hashJoin(
        $table_a,
        $table_b,
        array $keys,
        $table_a_name = 'table_a',
        $table_b_name = 'table_b'
    ): array
    {
        $hashTable = [];
        $out = [];

        // Create a hash table for table_b
        foreach ($table_b as $table_b_record) {
            // Create a composite key based on the values of all keys
            $hashKey = '';
            foreach ($keys as $key) {
                $hashKey .= $table_b_record[$key] . '|';
            }

            // Store the record in the hash table using the composite key
            $hashTable[$hashKey][] = $table_b_record;
        }

        // Loop over table_a and find matching records from the hash table
        foreach ($table_a as $table_a_record) {
            $hashKey = '';
            foreach ($keys as $key) {
                $hashKey .= $table_a_record[$key] . '|';
            }

            if (isset($hashTable[$hashKey])) {
                foreach ($hashTable[$hashKey] as $table_b_record) {
                    $out[] = array_merge(self::prefixKeys($table_a_record, $table_a_name), self::prefixKeys($table_b_record, $table_b_name));
                }
            }
        }

        return $out;
    }


    public static function sortMergeJoin(
        $table_a,
        $table_b,
        array $keys,
        $table_a_name = 'table_a',
        $table_b_name = 'table_b'
    ): array
    {
        $out = [];

        // Function to create a composite key for comparison
        $createCompositeKey = function ($record, $keys) {
            $keyValues = [];
            foreach ($keys as $key) {
                $keyValues[] = $record[$key];
            }
            return implode('|', $keyValues);
        };

        // Sort both tables by the join keys
        usort($table_a, function ($a, $b) use ($createCompositeKey, $keys) {
            return strcmp($createCompositeKey($a, $keys), $createCompositeKey($b, $keys));
        });

        usort($table_b, function ($a, $b) use ($createCompositeKey, $keys) {
            return strcmp($createCompositeKey($a, $keys), $createCompositeKey($b, $keys));
        });

        // Merge the two sorted tables
        $i = $j = 0;
        while ($i < count($table_a) && $j < count($table_b)) {
            $comp = strcmp($createCompositeKey($table_a[$i], $keys), $createCompositeKey($table_b[$j], $keys));

            if ($comp == 0) {  // Keys match
                $out[] = array_merge(self::prefixKeys($table_a[$i], $table_a_name), self::prefixKeys($table_b[$j], $table_b_name));

                // Check for additional matching records in table_b
                $nextB = $j + 1;
                while ($nextB < count($table_b) && $createCompositeKey($table_a[$i], $keys) === $createCompositeKey($table_b[$nextB], $keys)) {
                    $out[] = array_merge(self::prefixKeys($table_a[$i], $table_a_name), self::prefixKeys($table_b[$nextB], $table_b_name));
                    $nextB++;
                }

                $i++;
            } elseif ($comp < 0) {  // Table A key is less than Table B key
                $i++;
            } else {  // Table B key is less than Table A key
                $j++;
            }
        }

        return $out;
    }


    private static function prefixKeys($record, $prefix): array
    {
        $prefixed = [];
        foreach ($record as $key => $value) {
            $prefixed[$prefix . '_' . $key] = $value;
        }
        return $prefixed;
    }

}
