<?php
namespace DV\Json ;


class JsonValidate
{

    /**
     * @var mixed Json object providing data to be validated
     */
    protected $data;
    /**
     * @var mixed Json object providing schema rules
     */
    protected $schema;

    /**
     * @param $data
     * @param $schema
     */
    public function __construct($data=null, $schema)
    {
        if ($schema===null) {
            ## return false if schema or data == null
            return ;
        }
        $this->schema = json_decode($schema, true);
        $this->data = $data;
        if ($this->schema ===false){
            // Json decoding failure
            return ;
        }
    }

    /**
     * @return bool|string Returns true on success or a string of errors on failure.
     */
    public function validate($data = null)
    {
        if ($data!=null)
            $this->data = $data;
        // loop through each schema value and check if data exists
        $errors = "";
        foreach ($this->schema as $key => $value){
            // if specified in schema, the data key must be set and not "" (empty)
            if (!isset($this->data->$key)){
                // key not specified
                $errors.=$key." must be specified\n";
            } else {
                // check if rule is set for the value, if not, just check if it's not blank
                if ($value == ""){
                    // "" check
                    if ($this->data->$key == ""){
                        $errors.=$key." must not be blank\n";
                    }
                } else {
                    // validate via rule
                    $valid = $this->validateValue($this->data->$key, $value);
                    if ($valid!==true){
                        $errors.=$key." ".$valid;
                    }
                }
            }
        }
        // return true if no errors found
        if ($errors == ""){
            return true;
        }
        return $errors;
    }

    /**
     * Finds the specified schema rule and matches the value
     * @param $dataval
     * @param $schemaval
     * @return bool|string Returns true if the value is valid or an error string on failure
     */
    private function validateValue($dataval, $schemaval)
    {
        //if (strlen($schemaval)==1){
            // one character rules
            switch ($schemaval){
                case "~": // can be any value
                    return true;
                    break;
                case 1: // must be a number or float
                    if (!is_numeric($dataval)){
                        return "must be numeric\n";
                    }
                    break;
                case "@": // must be a valid email
                    if (strpos($dataval, "@")===false){
                        return "must be a valid email address\n";
                    }
                    break;
                case "[": // must be an array with at least 1 value
                    if (!is_array($dataval) || !sizeof($dataval)>0){
                        return "must be an array with at least one value\n";
                    }
                    break;
                case "{": // must be an object
                    if (!is_object($dataval)){
                        return "must be a json object\n";
                    }
                    break;
                case -1: // must be numeric but can be null
                    if ($dataval!=="" && !is_numeric($dataval)){
                        return "must be numeric\n";
                    }
                    break;
                case true: // must be numeric but can be null
                    if ($schemaval===true && !is_bool($dataval)){
                        return "must be a numeric or blank\n";
                        break;
                    }
                    break;
                default:
                    $rule = substr($schemaval, 0, 2);
                    $val = substr($schemaval, 2, strlen($schemaval));
                    switch ($rule){
                        case "!=": // must not equals
                            if ($dataval == $val){
                                return "must not equal ".$val."\n";
                            }
                            break;
                        case "<=": // must be no larger than
                            if ($dataval > $val){
                                return "must be no larger than ".$val."\n";
                            }
                            break;
                        case ">=": // must be equal or larger than
                            if ($dataval < $val){
                                return "must be larger than ".$val."\n";
                            }
                            break;
                    }
                    break;
            }
        return true;
    }

}