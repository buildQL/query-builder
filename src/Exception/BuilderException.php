<?php 

namespace BuildQL\Database\Query\Exception;

use Exception;

class BuilderException extends Exception{
    public function __construct(public string $msg){
        parent::__construct($msg);
    }

    /**
     *  Through Builder Exception Error Message
     */
    public function getErrorMessage(): string
    {
        return $this->msg . " - " . $this->getTraced();
    }

    /**
     *  Get error traced for user code
     */
    private function getTraced(): string
    {
        $trace = $this->getTrace();
        $userCode = end($trace);

        return sprintf("Check your code in %s at line %d.", basename($userCode['file']), $userCode['line']);
    }
}



?>