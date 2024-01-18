<?php

class MetricFrame {

    private array $data = [];
    private int $data_length = 0;

    private array $pre_predicate_transformations = [];
    private array $post_predicate_transformations = [];

    private array $select = [];
    private array $distincts = [];
    private array $distinct_counts = [];

    private array $predicates = [];
    private array $aggregations = [];
    private array $having;
    private bool $select_all = false;

    public function load_jsonl(
        string $filePath,
        string $as = NULL
    ): self
    {
        $split = explode("\n", file_get_contents($filePath));
        $data = [];
        foreach ($split as $k => $v) {
            $data[] = json_decode($v, true);
        }
        return $this->set_data_from_array($data);
    }


    public function set_data_from_array(
        array $data
    ): self
    {
        $this->data = $data;
        $this->data_length = sizeof($this->data);
        return $this;
    }

    public function set_select(
        array $select_array
    ): self
    {
        foreach ($select_array as $select) {

            /** Distinct agg functions get treated differently */
            if (is_object($select)) {
                $class = get_class($select);
                if (is_null($select->alias)) {
                    $select->alias = "$class({$select->column})";
                }
                switch ($class) {
                    case "CountDistinct":
                        $this->distinct_counts[] = $select->alias;
                        break;
                    case "Distinct":
                        $this->distincts[] = $select->alias;
                        break;
                }
            }
            if ($select === "ALL") {
                if (!empty($this->aggregations)) {
                    foreach ($this->aggregations as $aggregation) {
                        $this->select[] = $aggregation;
                    }
                }
                continue;
            }

            if ($select === "*") {
                $this->select_all = true;

            }


            $this->select[] = $select;
        }
        return $this;
    }

    public function set_agg(
        array $aggregations_array
    ): self
    {
        $this->aggregations = $aggregations_array;
        return $this;
    }

    public function set_pre_predicate_transformations(
        array $pre_predicate_transformations
    ): self
    {
        $this->pre_predicate_transformations = $pre_predicate_transformations;
        return $this;
    }

    public function set_post_predicate_transformations(
        array $post_predicate_transformations
    ): self
    {
        $this->post_predicate_transformations = $post_predicate_transformations;
        return $this;
    }

    public function set_predicates(
        array $predicates
    ): self
    {
        $this->predicates = $predicates;
        return $this;
    }

    public function set_having(
        array $having
    ): self
    {
        $this->having = $having;
        return $this;
    }


    private function validate_logic(): void
    {
    }

    public function execute(): array
    {
        # considering I am using builder pattern, logic for conflicting statements should be added here
        # to prevent dumb shit from being executed
        $this->validate_logic();

        # For loop instead of foreach as we are updating the data in place

        $out = [];

        $index = 0;
        for ($loop = 0; $loop < $this->data_length; $loop++) {
            $row = $this->data[$loop];

            /*** PRE-PREDICATE TRANSFORMATIONS */
            if (!empty($this->pre_predicate_transformations)) {
                foreach ($this->pre_predicate_transformations as $function_details) {
                    $response = $this->apply_column_transformation_function($row, $function_details);
                    $row[$response["key"]] = $response["value"];
                }
            }

            /** PREDICATE EVALUATION */
            if (!empty($this->predicates)) {
                foreach ($this->predicates as $predicate) {
                    if (!$predicate($this->data[$loop])) {
                        continue 2;
                    }
                }
            }

            /*** POST-PREDICATE TRANSFORMATIONS*/
            if (!empty($this->post_predicate_transformations)) {
                foreach ($this->post_predicate_transformations as $function_details) {
                    $response = $this->apply_column_transformation_function($row, $function_details);
                    $row[$response["key"]] = $response["value"];
                }
            }


            $key = "root";

            /**
             * AGGREGATIONS
             */
            if (!empty($this->aggregations)) {
                $key = "";
                foreach ($this->aggregations as $agg_column) {
                    $key .= "{$row[$agg_column]}";
                }
            }

            /**
             * SELECT * PATCH
             */
            if ($this->select_all) {
                $out[$key][] = $row;
            }


            /**
             * AGGREGATION FUNCTIONS
             */
            if (!empty($this->select)) {
                foreach ($this->select as $select) {
                    if (is_string($select)) {
                        # only take first row
                        # work on some logic here to figure out if this is actually legit
                        if (isset($out[$key][$select]) && !empty($this->aggregations)) continue;

                        if (!empty($this->aggregations)) {
                            $out[$key][$select] = $row[$select];
                        } else {
                            $out[$key][$select][] = $row[$select];
                        }

                    } else {
                        switch (get_class($select)) {
                            case "Count":
                                $out[$key][$select->alias] += isset($row[$select->column]);
                                break;
                            case "Sum":
                                $out[$key][$select->alias] += $row[$select->column];
                                break;
                            case "Distinct" || "CountDistinct":
                                $out[$key][$select->alias][$row[$select->column]] = $row[$select->column];
                                break;
                        }
                    }
                }
            }
        }


        if (!empty($this->having)) {
            foreach ($out as $k => $v) {
                foreach ($this->having as $has) {
                    if (!$has($v)) {
                        unset($out[$k]);
                    }
                }
            }
        }

        $out = array_values($out);


        # Distinct-s Require Some Clean Up After-the-fact
        if (!empty($this->distinct_counts)) {
            foreach ($out as $key => $data) {
                foreach ($this->distinct_counts as $distinct_alias) {
                    $out[$key][$distinct_alias] = sizeof($data[$distinct_alias]);
                }
            }
        }

        # Distinct-s Require Some Clean Up After-the-fact
        if (!empty($this->distincts)) {
            foreach ($out as $key => $data) {
                foreach ($this->distincts as $distinct_alias) {
                    $out[$key][$distinct_alias] = array_values($data[$distinct_alias]);
                }
            }
        }


        return $out;
    }

    private function apply_column_transformation_function(
        $row,
        $function_details,
    ): array
    {

        $source_column_or_columns = $function_details->columns_or_column;
        $fnOutputCol = $function_details->output_column;
        $function = $function_details->function;


        /** Grab the value of column or values of columns */
        if (is_array($source_column_or_columns)) {
            $value_or_values = [];
            foreach ($source_column_or_columns as $column) {
                $value_or_values[] = $row[$column];
            }

        } else {
            $value_or_values = $row[$source_column_or_columns];
        }

        /** Create output column name if none passed in */
        if (is_null($fnOutputCol)) {
            $output_column_name = $source_column_or_columns;
            if (is_array($source_column_or_columns)) {
                $output_column_name = implode(",", $source_column_or_columns);
            }
            # todo this is a bit shit
            $fnOutputCol = "f($output_column_name)$fnOutputCol";
        }
        return ["key" => $fnOutputCol, "value" => $function($value_or_values)];
    }


}