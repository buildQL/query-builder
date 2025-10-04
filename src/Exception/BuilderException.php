<?php 

namespace BuildQL\Database\Query\Exception;

use Exception;

class BuilderException extends Exception{
    public function __construct(public string $msg, public bool $trace = true){
        parent::__construct($msg);
    }

    /**
     *  Through Builder Exception Error Message
     */
    public function getErrorMessage(): string
    {
        $msg = $this->msg . " - " . $this->getTraced();
        return $this->trace ? $msg : $this->msg;
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