<?php 

namespace BuildQL\Database\Query\Traits;

use BuildQL\Database\Query\Exception\BuilderException;

trait QueryConversion{
    /**
     *  Convert properties that are being set by method into Raw SQL Query
     */
    protected function convertToSQL(string $methodCalledHim = "get", array $data = []): void
    {
        $methodCalledHim = strtolower($methodCalledHim);

        if (!in_array($methodCalledHim, $this->methodThatShouldReturnQueryResults)){
            throw new BuilderException("Invalid method called $methodCalledHim()");
        }
        
        if (in_array($methodCalledHim, ['delete', 'update', 'get'])){
            if ($this->where || $this->whereIn){
                $whereValues = [];
                if ($this->where){
                    $whereCol = [];
                    foreach ($this->where as [$column, $oper, $value, $boolean]){
                        if ($whereCol == []) $boolean = '';
                        $column = $this->changeColumnFormat($column);
                        $wCol = "$boolean $column $oper";
                        // check value column is not null
                        // null means user want to fetch record if it is null
                        // when value is null then cannot be append $value var in $whereValues array
                        if ($value != null){
                            $wCol .= " ?";
                            $whereValues[] = $value;
                        }
                        $whereCol[] = $wCol;
                    }
                    $whereCol = implode(" ", $whereCol);
                }
                if ($this->whereIn){
                    $whereInCol = [];
                    foreach ($this->whereIn as [$column, $value, $boolean, $whereNotIn]){
                        if ($this->where == [] && $whereInCol == []) $boolean = "";
                        $column = $this->changeColumnFormat($column);
                        $oper = $whereNotIn ? "NOT IN" : "IN";
                        $whereInCol[] = "$boolean $column $oper (". rtrim(str_repeat("?,", count($value)), ",") .")";
                        foreach ($value as $val){
                            $whereValues[] = $val;
                        }
                    }
                    $whereInCol = implode(" ", $whereInCol);
                }

                if ($this->where && $this->whereIn){
                    $where = $whereCol . " " . $whereInCol;
                }
                else{
                    $where = ($this->where) ? $whereCol : $whereInCol;
                }

                // assign where clause values to a protected member property
                $this->whereBindingValues = $whereValues;
            }
            elseif (in_array($methodCalledHim, ['delete', 'update'])){
                throw new BuilderException("Where method is not optional in $methodCalledHim case");
            }
        }


        if ($methodCalledHim == 'insert'){
            $query = "INSERT INTO $this->table (`". implode("`, `", array_keys($data)) ."`) VALUES (". rtrim(str_repeat("?, ", count($data)), ", ") .")";
        }

        elseif ($methodCalledHim == 'update'){
            $col = [];
            foreach ($data as $key => $val){
                $key = $this->changeColumnFormat($key);
                $col[] = "$key = ?";
            }
            // update query ready
            $query = "UPDATE $this->table SET " . implode(", ", $col) . " WHERE" . $where;
        }

        elseif ($methodCalledHim == "delete"){
            $query = "DELETE FROM $this->table WHERE " . $where;
        }

        elseif ($methodCalledHim == "all"){
            $query = "SELECT * FROM $this->table";
        }

        elseif ($methodCalledHim == "get"){

            $column = implode(", ", array_map(function ($col){
                return $this->changeAggregateColumnFormat($col);
            }, $this->columns));
            
            // adding aggregates columns in select command
            if ($this->aggregates){
                // $this->aggregates = ['count' => "name"];
                $aggregates = array_filter($this->aggregates, function ($col){
                    return !empty($col);
                }); 

                // if all aggregate func null then jump out from this block of code
                if (empty($aggregates)) goto jump;

                $aggregateCol = [];
                foreach($aggregates as $key => $val){
                    $colAndAlias = explode(":", $val);
                    $changeFormat = $this->changeColumnFormat($colAndAlias[0]);
                    $aggregateCol[] = isset($colAndAlias[1]) ? "$key($changeFormat) as `$colAndAlias[1]`" : "$key($changeFormat)";
                }
                $aggregateCol = implode(", ", $aggregateCol);
                $column = empty($column) ? $aggregateCol : "$column, $aggregateCol";
            }

            jump:

            $distinct = $this->distinct ? "DISTINCT $column" : $column;
            $query = "SELECT $distinct FROM $this->table";

            if ($this->joinTable){
                $join = [];
                foreach ($this->joinTable as [$joinTable, $firstCol, $secondCol, $type]){
                    $joinTable = $this->changeColumnFormat($joinTable);
                    if ($type == "CROSS"){
                        $join[] = " $type JOIN $joinTable";
                    }
                    else{
                        if (!$firstCol || !$secondCol){
                            $nullCol = $firstCol ? "secondCol" : "firstCol";
                            throw new BuilderException("Null argument : \$$nullCol() argu must not be empty in $type join case.");
                        }
                        $firstCol = $this->changeColumnFormat($firstCol);
                        $secondCol = $this->changeColumnFormat($secondCol);
                        $join[] = " $type JOIN $joinTable ON $firstCol = $secondCol";
                    }
                }
                $query .= implode("", $join);
            }

            if (isset($where)){
                $query .= " WHERE" . $where;
            }

            if ($this->groupBy){
                $groupBy = implode(", ", $this->changeColumnFormat($this->groupBy));
                $query .= " GROUP BY $groupBy";
            }

            if ($this->having){
                $havingCol = [];
                $havingValues = [];
                foreach ($this->having as [$col, $oper, $value, $boolean]){
                    if ($havingCol == []) $boolean = '';
                    $col = $this->changeAggregateColumnFormat($col);
                    $havingCol[] = "$boolean $col $oper ?";
                    $havingValues[] = $value;
                }
                $havingCol = implode(" ", $havingCol);
                $query .= " HAVING" . $havingCol;

                // assign having clause values for binding
                $this->havingBindingValues = $havingValues;
            }

            if ($this->orderBy){
                $orderBy = [];
                foreach ($this->orderBy as [$col, $sort]){
                    $col = $this->changeColumnFormat($col);
                    $orderBy[] = "$col $sort";
                }
                $query .= " ORDER BY " . implode(", ", $orderBy);
            }

            if (isset($this->limit)){
                if ($this->offset != null){
                    $query .= " LIMIT $this->offset, $this->limit";
                }
                else{
                    $query .= " LIMIT $this->limit";
                }
            }
        }

        $this->rawSQL = $query;
    }

    /**
     *  Change column format;
     *  e.g. convert table.column:alias to `table`.`column` as `alias`
     */
    private function changeColumnFormat(array|string $columns): array|string
    {
        $cols = is_array($columns) ? $columns : [$columns];
        $mapped = array_map(function ($col){
            // validate column name
            $this->validateColumn($col);
            $colAndAlias = explode(":", $col);
                if (preg_match("/^[a-z0-9_-]+\.\*$/i", $colAndAlias[0])){
                    $col = "`" . implode("`.", explode(".", $colAndAlias[0]));
                }
                elseif ($colAndAlias[0] == "*"){
                    $col = "*";
                }
                else{
                    $col = "`" . implode("`.`", explode(".", $colAndAlias[0])) . "`";
                }
                if (isset($colAndAlias[1])) $col .=  " as `" . $colAndAlias[1] . "`";
                return $col;
            }, $cols);
        return is_array($columns) ? $mapped : $mapped[0];
    }


    /**
     *  Change aggregate column format;
     *  e.g. convert count(table.column):alias to count(`table`.`column`) as `alias`
    */
    private function changeAggregateColumnFormat(string|array $columns): string|array
    {
        $cols = is_array($columns) ? $columns : [$columns];
        $mapped = array_map(function ($column){
            if (preg_match("/^([a-z]+)\(([a-z0-9._*-]+)\)(?:\:([a-z0-9_]+))?/i", $column, $matches)){
                [$original, $func, $col] = $matches;
                // validate column name
                $this->validateColumn($col);
                $alias = $matches[3] ?? false;
                $col = $this->changeColumnFormat($col);

                $aggregateCol = $func . "($col)";
                if ($alias) {
                    // validate alias name
                    $this->validateColumn($alias);
                    $aggregateCol .= " as `$alias`";
                }
                return $aggregateCol;
            }
            else{
                return $this->changeColumnFormat($column);
            }
        }, $cols);
        return is_array($columns) ? $mapped : $mapped[0];
    }

    /**
     *  Validate columns name passed by the developer,
     *  Ensure column only contains specific characters
     */
    private function validateColumn(string $column) : true
    {
        if (preg_match("/[^a-z0-9_\.\-\*\:]/i", $column)){
            throw new BuilderException("Invalid column name : " . $column);
        }   
        return true;
    }
}



?>