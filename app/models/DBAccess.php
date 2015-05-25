<?php
/**
 * Created by PhpStorm.
 * User: helenpham
 * Date: 11/7/14
 * Time: 11:18
 */

class DBAccess {

    protected $table_name;

    public function __construct($name)
    {
        $this->table_name=$name;
    }

    /**
     * @param $param array key and value for insert
     */
    public function insert($param)
    {
        if(isset($this->table_name)) {
            if($param==null || (isset($param) && count($param)==0)) {
                throw new APIException("insert param invalid",APIException::ERRORCODE_LACK_PARAMETER);
            }

            $result=$this->formatValue(array_values($param));
            $values=$result['values'];
            $binding=$result['binding'];
            $sql="insert into ".$this->table_name." (".implode(',',array_keys($param)).") values (".implode(",",$values).")";

            $db = DBConnection::write();

            $db->insert($sql,$binding);

            return $db->getPdo()->lastInsertId();
        } else {
            throw new APIException("Table name invalid",APIException::ERRORCODE_INVALID_INPUT);
        }

    }

    private function formatValue($value_inputs) {
        if(isset($value_inputs)) {
            $values=array();
            $binding=array();
            foreach($value_inputs as $val) {
                if(is_array($val)) {
                    $values[]=array_shift($val);
                } else {
                    $values[]="?";
                    $binding[]=$val;
                }
            }
            return array('values'=>$values,'binding'=>$binding);
        } else {
            return array('values'=>array(),'binding'=>array());
        }
    }
    /**
     * @param $set_Params : key-value array about field want to update value
     * @param $where : where clause for update query
     * @return : the number of record effected
     */
    public function update($set_Params,$where)
    {
        if(isset($this->table_name)) {

            if($set_Params==null || (isset($set_Params) && count($set_Params)==0)) {
                throw new APIException("set param invalid",APIException::ERRORCODE_LACK_PARAMETER);
            }

            if($where==null || (isset($where) && count($where)==0)) {
                throw new APIException("where param invalid",APIException::ERRORCODE_LACK_PARAMETER);
            }

            $exist_in_where=array();

            $binding=array();

            $sets=array();
            $wheres=array();
            foreach ($set_Params as $key => $value) {
                if(array_key_exists($key,$where)) {
                    $exist_in_where[$key]=$value;
                }
                if(is_array($value)) {
                    $sets[]=$key."=".array_shift($value);
                } else {
                    $sets[]=$key."=?";
                    $binding[]=$value;
                }
            }

            foreach ($where as $key => $value) {
                if(is_array($value)) {
                    $wheres[]=$key."=".array_shift($value);
                } else {
                    $wheres[]=$key."=?";
                    $binding[]=$value;
                }
            }

            $sql="Update {$this->table_name} set ".implode(",",$sets)." where ".implode(" and ",$wheres);

            DBConnection::write()->update($sql,$binding);
            // correct where clause, use when update cache object only
            foreach ($exist_in_where  as $key=>$value) {
                $where[$key]=$value;
            }
            return $where;

        } else {
            throw new APIException("Table name invalid",APIException::ERRORCODE_INVALID_INPUT);
        }
    }

    /**
     * Update multiple records
     */
    public function updates($field_values)
    {
        throw new Exception("getObjectsByField function from $this->table_name has not yet implemented");
    }

    /**
     * @param $where : where clause on delete query
     * @return : The number of record effected
     */
    public function delete($where)
    {
        if(isset($this->table_name)) {

            if($where==null || (isset($where) && count($where)==0)) {
                throw new APIException("delete param invalid",APIException::ERRORCODE_LACK_PARAMETER);
            }
            $fields=array();
            $binding=array();

            foreach ($where as $key => $value) {
                $fields[]=$key."=?";
                if(is_array($value)) {
                    $binding=array_shift($value);
                } else {
                    $binding[]=$value;
                }
            }


            $sql="DELETE FROM {$this->table_name} WHERE ".implode(" and ",$fields);

            return DBConnection::write()->delete($sql,$binding);
        }
        return false;
    }

    /**
     * Insert multiable values
     * @param array $fields
     * @param array $fieldValues
     * @return array
     */
    public function inserts($fields = array(), $fieldValues = array()) {
        $values = array();
        $binding=array();
        foreach($fieldValues as $value){
            if(is_array($value)) {
                $valueArr = array();
                foreach ($fields as $field) {
                    if(isset($value[$field])) {
                        $val = isset($value[$field]) ? $value[$field] : '';
                        if(is_array($val)) {
                            $valueArr[] = array_shift($val);
                        } else {
                            $valueArr[] = "?";
                            $binding[]=$val;
                        }
                    } else {
                        $valueArr[] = "?";
                        $binding[]='';
                    }
                }
                $values[] = "(". implode(",", $valueArr) .")";
            }
        }
        if(count($fields) && count($values)) {
            $sql = "INSERT INTO {$this->table_name} (". implode(',', $fields) .") VALUES ". implode(',', $values);
            $db = DBConnection::write();
            $db->insert($sql,$binding);
            $first_id= $db->getPdo()->lastInsertId();

            $ids=array();
            $number_record=count($fieldValues);
            for($i=0;$i<$number_record;$i++) {
                array_push($ids,$first_id+$i);
            }
            return $ids;
        }
        return array();
    }
    /**
     * @param $id : the object id need to check
     * @return true if object is exist on database, false if invert
     */
    public function isValid($id) {
        $sql="select count(*) as count from {$this->table_name} where id=?";
        $result=DBConnection::read()->select($sql,array($id));

        if($result[0]->count>0) {
            return true;
        } else {
            return false;
        }
    }
    public function getOneObjectByField($field) {
        $objects=$this->getObjectsByField($field);
        if(count($objects)>0) {
            return $objects[0];
        } else {
            return null;
        }
    }

    /**
     * @param $fields : the array of field name and value
     * @throws Exception : throw exception if this method is not implement by child class
     * @return : array of Objects
     */
    public  function  getObjectsByField($fields)
    {
        $query=array();

        foreach ($fields as $key => $value) {
            $query[]=$key."='".$value."'";
        }

        if(count($query)==0) {
            $sql="select * from {$this->table_name}";
        } else {
            $sql="select * from {$this->table_name} where ".implode(" and ",$query);
        }

        $result=DBConnection::read()->select($sql);
        if(count($result)>0) {
            return $result;
        } else {
            return array();
        }
    }

    /**
     * @param $fields : array of field names and value
     * @return : array of objects
     * @throws Exception : throw exception if this function has not yet implemented by child class
     */
    public function getObjectsByFields($fields)
    {
        if(!is_array($fields)) {
            throw new Exception("Invalid input in getObjectsByFields from {$this->table_name} object");
        }
        $query=array();
        foreach ($fields as $field =>$value) {
            if(!is_array($value)) {$value=array($value);}
            $query[]=$field." in ('".implode("','",$value)."') ";
        }

        $sql="select * from {$this->table_name} where ".implode(" and ",$query);

        $result=DBConnection::read()->select($sql);
        return $result;
    }
}