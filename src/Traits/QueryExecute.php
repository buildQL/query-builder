<?php 

namespace BuildQL\Database\Query\Traits;

use BuildQL\Database\Query\Exception\BuilderException;

trait QueryExecute{
    /**
     *  This method prepare query and execute them
     */
    protected function prepareAndExecuteQuery(string $methodCalledHim = "get", array $binding = []): array|bool
    {
        // turn off mysql exception
        mysqli_report(MYSQLI_REPORT_OFF);
        $methodCalledHim = strtolower($methodCalledHim);

        if (!in_array($methodCalledHim, $this->methodThatShouldReturnQueryResults)){
            throw new BuilderException("Invalid method called $methodCalledHim()");
        }
        
        $prepare = $this->conn->prepare($this->rawSQL);
        $bind = false;
        if ($this->whereBindingValues || $binding || $this->havingBindingValues){
            $bind = true;
        }
        
        if ($prepare){
            if ($bind){
                // neglect unwanted leading and trailing spacing
                $binding = array_map("trim", $binding);

                $bind_values = array_merge(array_values($binding), $this->whereBindingValues, $this->havingBindingValues);

                $bind_type = "";
                foreach($bind_values as $val){
                    $bind_type .= is_double($val) || is_float($val) ? "d" : (is_int($val) ? "i" : "s");
                }

                $prepare->bind_param($bind_type, ...$bind_values);
            }

            if ($prepare->execute()){
                if (in_array($methodCalledHim, ['all', 'get', 'find', 'first', "count"])){
                    return $prepare->get_result()->fetch_all(MYSQLI_ASSOC);
                }
                else{
                    return true;
                }
            }
            else{
                throw new BuilderException("Query Execution Failed " . $this->conn->error);
            }
        }
        else{
            throw new BuilderException("Query Preparation Failed " . $this->conn->error);
        }
    }
}




?>