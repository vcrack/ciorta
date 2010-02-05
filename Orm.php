<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

if ( !defined('ALL') ) define('ALL', 'all');
if ( !defined('IS_NULL') ) define ('IS_NULL', ' is null');
if ( !defined('NOT_NULL') ) define ('NOT_NULL', ' <> ""');

/**
 * An open source application development framework 
 *
 * @author        Jan Kristanto (jan_kristanto@yahoo.co.id)
 * @copyright    Copyright (c) 2009.
 * @link        http://jan.web.id
 * @Version     0.01
 */
 
 /**
  * [Object Relational Mapping][ref-orm] (ORM) is a method of abstracting database
  * access to standard PHP calls.
  * 
  *  
  * 
  */


class Orm {
    
    var $name = null;
    
    var $table =  null;
    
    var $primaryKey = null;
    
    var $alias = null;
    
    var $prefix = '';
    
    var $index = array('PRI' => 'primary', 'MUL' => 'index', 'UNI' => 'unique');
    
    var $belongsTo = array();
    
    var $hasOne = array();
    
    var $hasMany = array();
    
    var $hasAndBelongsToMany = array();
    
    var $sqlLog = array();
    
    var $relation = true; 
    
    var $__associationKeys = array(
            'belongsTo' => array('className', 'withTable', 'foreignKey'),
            'hasOne' => array('className','withTable', 'foreignKey', 'dependent'),
            'hasMany' => array('className', 'foreignKey','withTable','dependent'),
            'hasAndBelongsToMany' => array('className', 'joinTable','foreignKey', 'associationForeignKey'));
    
    var $__associations = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany');
    
    
     
    function Orm($name = null,$table = null,$primaryKey=null){
        $this->_assignLibraries();
        $this->_loadHelpers();
        
        if($name == null){
            if ($this->name == null){
                $this->name = ucfirst(singular(get_class($this)));             
            }
        }else{
            $this->name = $name;    
        }
        
        if ($this->alias === null) {
            $this->alias = $this->name;
        }
       
        if($table == null){
            if ($this->table == null) { 
                $this->table = plural($this->name);
            }
        }else{
            $this->table = $table;
        }    
        $this->table = $this->prefix . $this->table;
        
        if($primaryKey == null){
            if ($this->primaryKey == null) {
                $this->primaryKey = 'id';
            }
        }else{
           $this->primaryKey = $primaryKey;
        }
        
        Registry::addObject($this->alias, $this);
        $this->createLinks();
            
    }
    
    
    function createLinks(){
        foreach($this->__associations as $type){
            foreach($this->{$type} as $assoc => $value){
                
                $this->__constructLinkedModel(ucfirst($assoc));
                    
            }
        }   
    }
    
    function __constructLinkedModel($assoc, $className = null) {
        if (empty($className)) {
            $className = $assoc;
        }

        if (!isset($this->{$assoc}) || $this->{$assoc}->name !== $className) {
            $model = array('class' => $className, 'alias' => $assoc);
            if (PHP_VERSION >= 5) {
                $this->{$assoc} = Registry::init($model);
            } else {
                $this->{$assoc} =& Registry::init($model);
            }
            
        }
    }                                           
    
    function autoConfig($model, $linkedModel,$type){
        switch ($type) {
            case 'belongsTo':
                if(!isset($model['belongsTo'][$linkedModel->name]['className'])){
                    $model['belongsTo'][$linkedModel->name]['className'] = $linkedModel->name;    
                }
                
                if(!isset($model['belongsTo'][$linkedModel->name]['withTable'])){
                    $model['belongsTo'][$linkedModel->name]['withTable'] = $linkedModel->table;    
                }
                
                if(!isset($model['belongsTo'][$linkedModel->name]['foreignKey'])){
                    $model['belongsTo'][$linkedModel->name]['foreignKey'] = singular($linkedModel->table) . '_' . $linkedModel->primaryKey;    
                }
                
                if(!isset($model['belongsTo'][$linkedModel->name]['dependent'])){
                    $model['belongsTo'][$linkedModel->name]['dependent'] = TRUE;    
                }
            break;
            case 'hasOne' :
            
            if(!isset($model['hasOne'][$linkedModel->name]['className'])){
                    $model['hasOne'][$linkedModel->name]['className'] = $linkedModel->name;    
            }                      
            
            if(!isset($model['hasOne'][$linkedModel->name]['withTable'])){
                    $model['hasOne'][$linkedModel->name]['withTable'] = $linkedModel->table;    
            }
            
            if(!isset($model['hasOne'][$linkedModel->name]['foreignKey'])){
                    $model['hasOne'][$linkedModel->name]['foreignKey'] = singular($model->table) . '_'. $model->primaryKey;    
            }
            
            if(!isset($model['hasOne'][$linkedModel->name]['dependent'])){
                    $model['hasOne'][$linkedModel->name]['dependent'] = TRUE;    
            }
            
            
            break;
            case 'hasMany' :
            
            if(!isset($model['hasMany'][$linkedModel->name]['className'])){
                    $model['hasMany'][$linkedModel->name]['className'] = $linkedModel->name;    
            }                      
            
            if(!isset($model['hasMany'][$linkedModel->name]['withTable'])){
                    $model['hasMany'][$linkedModel->name]['withTable'] = $linkedModel->table;    
            }
            
            if(!isset($model['hasMany'][$linkedModel->name]['foreignKey'])){
                    $model['hasMany'][$linkedModel->name]['foreignKey'] = singular($model->table) . '_'. $model->primaryKey;    
            }
            
            if(!isset($model['hasMany'][$linkedModel->name]['dependent'])){
                    $model['hasMany'][$linkedModel->name]['dependent'] = TRUE;    
            }
            break;
            case 'hasAndBelongsToMany' :
            
            if(!isset($model['hasAndBelongsToMany'][$linkedModel->name]['className'])){
                    $model['hasAndBelongsToMany'][$linkedModel->name]['className'] = $linkedModel->name;    
            }                      
            
            if(!isset($model['hasAndBelongsToMany'][$linkedModel->name]['associationForeignKey'])){
                    $model['hasAndBelongsToMany'][$linkedModel->name]['associationForeignKey'] = singular($linkedModel->table).'_'.$linkedModel->primaryKey;    
            }
            
            if(!isset($model['hasAndBelongsToMany'][$linkedModel->name]['foreignKey'])){
                    $model['hasAndBelongsToMany'][$linkedModel->name]['foreignKey'] = singular($model->table) . '_'. $model->primaryKey;    
            }
            
            break;
        }     
    }
    
    function mergeHasMany($result,$merge,$association,$model,$linkmodel){
        foreach ($result as $i => $value) {
            $count = 0;
            $merged[$association] = array();
            foreach ($merge as $j => $data) {
                if (isset($value[$model->alias]) && $value[$model->alias][$model->primaryKey] 
                === $data[$association][$model->hasMany[$association]['foreignKey']]) {
                    if (count($data) > 1) {
                        $data = array_merge($data[$association], $data);
                        unset($data[$association]);
                        foreach ($data as $key => $name) {
                            if (is_numeric($key)) {
                                $data[$association][] = $name;
                                unset($data[$key]);
                            }
                        }
                        $merged[$association][] = $data;
                    } else {
                        $merged[$association][] = $data[$association];
                    }
                }
                $count++;
            }
            if (isset($value[$model->alias]) && !empty($merged[$association])) {
                $result[$i] = $this->pushDiff($result[$i], $merged);
                unset($merged);
            }
        }
        return $result;        
    }
    
    function mergeHABTM($result,$merge,$association,$model,$aliasJoinTable){
        foreach ($result as $i => $value) {
            $count = 0;
            $merged[$association] = array();
            foreach ($merge as $j => $data) {
                if (isset($value[$model->alias]) && 
                $value[$model->alias][$model->primaryKey] === 
                $data[$aliasJoinTable]
                [$model->hasAndBelongsToMany[$association]['foreignKey']]) {
                    if (count($data) > 1) {
                        $data = array_merge($data[$association], $data);
                        unset($data[$association]);
                        foreach ($data as $key => $name) {
                            if (is_numeric($key)) {
                                $data[$association][] = $name;
                                unset($data[$key]);
                            }
                        }
                        $merged[$association][] = $data;
                    } else {
                        $merged[$association][] = $data[$association];
                    }
                }
                $count++;
            }
            if (isset($value[$model->alias]) && !empty($merged[$association]) ) {
                $result[$i] = $this->pushDiff($result[$i], $merged);
                unset($merged);
            }
        }
        return $result;    
    }
    
    function pushDiff($array, $array2) {
        if (empty($array) && !empty($array2)) {
            return $array2;
        }
        if (!empty($array) && !empty($array2)) {
            foreach ($array2 as $key => $value) {
                if (!array_key_exists($key, $array)) {
                    $array[$key] = $value;
                } else {
                    if (is_array($value)) {
                        $array[$key] = $this->pushDiff($array[$key], $array2[$key]);
                    }
                }
            }
        }
        return $array;
    }
    
    function mergeResult($resultAll,$result,$association,$model,$linkmodel){
        if($resultAll == null){
            return $result;
        }
        
        foreach ($resultAll as $i => $value) {
            $count = 0;
            $merged[$association] = array();
            foreach ($result as $j => $data) {
                if (isset($value[$model->alias]) && 
                $value[$model->alias][$model->primaryKey] === 
                $data[$model->alias][$model->primaryKey]) {
                        if(isset($data[$association])){            
                            $merged[$association] = $data[$association];
                        }
                    }         
                $count++;
            }
            if (isset($value[$model->alias]) && !empty($merged[$association]) ) {
                $resultAll[$i] = $this->pushDiff($resultAll[$i], $merged);
                unset($merged);
            }
        }
        
        return $resultAll;
    }
    
    function read($model,$queryData){        
        $resultAll = null;
        if($this->relation == true){
        foreach ($model->__associations as $type) {
            foreach ($model->{$type} as $assoc => $assocData) {
                $linkModel = $model->{$assoc};
        
                $result = array();       
                $result = $this->generateAssociationQuery($model,$linkModel,
                $type, $assoc, $assocData, $queryData);
                    
                $resultAll = $this->mergeResult($resultAll,$result,
                $assoc,$model,$linkModel);
            }
        }
        }
        
        if($resultAll == null){
                $query['conditions']        = $queryData['conditions'];
                if(empty($queryData['fields'])){
                    $query['field'] = $this->getFieldTable($model);
                }
                else{
                    $query['field']             = $queryData['fields'];
                }
                
                $query['from']              = $model->table . ' AS ' . $model->alias;
                $query['order']             = $queryData['order'];
                $query['limit']             = $queryData['limit'];
                
                $resultAll = $this->renderQuery($query,$model);        
        }
        return $resultAll;            
    }
    
    
    
    function generateAssociationQuery($model,$linkmodel,$type,$nameLinkModel,$configAssoc,$queryData){
        
        $query = array();
                                                                             
        switch ($type) {
            case 'belongsTo':
                $query['conditions']        = $queryData['conditions'];
                if(empty($queryData['fields'])){
                    $query['field'] = $this->getField(array($model,$linkmodel));
                }else{
                    $query['field']             = $queryData['fields'];
                }
                $query['from']              = $model->table . ' AS ' . $model->alias;
                $query['order']             = $queryData['order'];
                $query['limit']             = $queryData['limit'];
                $query['join_table']        = $linkmodel->table . ' AS ' . $linkmodel->alias;
                $query['join_condition']    = $linkmodel->alias . '.' . $linkmodel->primaryKey
                 .' = ' . $model->alias . '.' . $configAssoc['foreignKey'];
                $query['join_type']         = 'left';  
                
                $newModel = $model; 
                return $this->renderQuery($query,$newModel);           
            break;
            case 'hasOne':
                $query['conditions']        = $queryData['conditions'];
                if(empty($queryData['fields'])){
                    $query['field'] = $this->getField(array($model,$linkmodel));
                }else{
                    $query['field']             = $queryData['fields'];
                }
                $query['from']              = $model->table . ' AS ' . $model->alias;
                $query['order']             = $queryData['order'];
                $query['limit']             = $queryData['limit'];
                $query['join_table']        = $linkmodel->table . ' AS ' . $linkmodel->alias;
                $query['join_condition']    =  $model->alias . '.' . $model->primaryKey .' = ' .
                  $linkmodel->alias . '.' . $configAssoc['foreignKey'];
                $query['join_type']         = 'left';  
                
                $newModel = $model;
                return $this->renderQuery($query,$newModel);            
            break;
            case 'hasMany':
                $querysatu = array();
                
                $querysatu['conditions'] = $queryData['conditions'];
                $querysatu['field']      = $this->getFieldTable($model);
                $querysatu['from']       = $model->table . ' AS ' . $model->alias;
                $querysatu['order']      = $queryData['order'];
                $querysatu['limit']      = $queryData['limit'];
                
                $newModel = $model; 
                $resultsatu = $this->renderQuery($querysatu,$newModel);
                
                $idModel = null;
                if(count($resultsatu) == 1){
                    $query['conditions'] = $linkmodel->alias . '.' 
                    . $configAssoc['foreignKey'] . ' = '
                    .$resultsatu[0][$newModel->alias][$newModel->primaryKey];    
                }
                else{
                    if(count($resultsatu) > 0 ){ 
                        foreach($resultsatu as $h){
                            $idModel .=  ',' . $h[$newModel->alias][$newModel->primaryKey] ;        
                        }
                        
                        $idModel =  substr($idModel, 1);
                        $query['conditions'] = $linkmodel->alias . '.'
                         . $configAssoc['foreignKey'] . ' IN (' . $idModel . ')';    
                    }
                }              
                $query['field']             = $this->getFieldTable($linkmodel);
                $query['from']              = $linkmodel->table . ' AS ' . $linkmodel->alias;
                $resultdua = $this->renderQuery($query,$newModel);
                
                return $this->mergeHasMany($resultsatu,$resultdua,
                $nameLinkModel,$model,$linkmodel);         
            break;
            case 'hasAndBelongsToMany':
                $querysatu = array(); 
                $aliasJoinTable = singular($configAssoc['joinTable']);
                
                $querysatu['conditions']  = $queryData['conditions'];
                $querysatu['field']       = $this->getFieldTable($model,null);
                $querysatu['from']        = $model->table . ' AS ' . $model->alias;
                $querysatu['order']       = $queryData['order'];
                $querysatu['limit']       = $queryData['limit'];
                
                $newModel = $model;
                $resultsatu = $this->renderQuery($querysatu,$newModel);
                
                $idModel = null;
                if(count($resultsatu) > 0){
                    $query['join_table'] = $configAssoc['joinTable'] . ' AS ' . $aliasJoinTable;         
                    foreach($resultsatu as $h){
                        $idModel .=  ',' . $h[$newModel->alias][$newModel->primaryKey];        
                    }         
                    $idModel =  substr($idModel, 1);
                    $query['join_condition']    = 
                    $aliasJoinTable . '.' .$configAssoc['foreignKey'] 
                        . ' IN( '. $idModel . ')'
                        . ' AND ' . $aliasJoinTable . '.' 
                        . $configAssoc['associationForeignKey'] . ' = ' 
                        . $linkmodel->alias . '.' . $linkmodel->primaryKey ; 
                        ;                                 
                    $query['fieldsatu'] = $this->getFieldTable($linkmodel);
                    $query['fielddua']  = $this->getFieldTable(null,$configAssoc['joinTable']);
                    $query['from']      = $linkmodel->table . ' AS ' . $linkmodel->alias;
                    $query['field'] = array_merge($query['fieldsatu'],$query['fielddua']);
                    $resultdua = $this->renderQuery($query,$newModel);
                    return $this->mergeHABTM($resultsatu,$resultdua,
                    $nameLinkModel,$model,$aliasJoinTable);
                }
                return $resultsatu;
            break;
        }
        
        
        
    }
    
    function renderQuery($query,$model){        
        if(isset($query['conditions'])){
            $this->where($query['conditions']);
        }
        
        if(isset($query['field'])){
            $this->select($query['field']);
        }
        
        if(isset($query['from'])){
            $this->from($query['from']);
        }
        
        if(isset($query['order'])){
            $this->orderBy($query['order']);
        }
        
        if(isset($query['limit'])){
            $this->limit($query['limit']);
        }     
        if(isset($query['join_table'])){         
            if(isset($query['join_type'])){
                $this->join($query['join_table'],
                $query['join_condition'],$query['join_type']);
            }
            else{
                $this->join($query['join_table'],$query['join_condition']);
            }
        }
        
        $get = $this->db->get();
        $this->sqlLog[] = $this->db->last_query();           
        return $this->getData($get); 
        //return $get->result();                                                                       
    }
                                                      
    function getData($obj){
        $out = array();
        
        $map = $this->mappingField($obj);
    
        while ($this->hasResult($obj->result_id) && $item = $this->fetchRow($obj,$map)) {
            $out[] = $item;
        }
            
        return $out;
           
    }
    
    function hasResult($id) {
        return is_resource($id);
    }
    
    function mappingField($obj){
        
        $results =& $obj->result_id;
        $map = array();
        $numFields = mysql_num_fields($results);
        $index = 0;
        $j = 0;

        while ($j < $numFields) {
            $column = mysql_fetch_field($results,$j);
            if (!empty($column->table)) {
                $map[$index++] = array($column->table, $column->name);
            } else {
                $map[$index++] = array(0, $column->name);
            }
            $j++;
        }
        
        return $map;
    }
    
    function fetchRow($obj,$map){
        $results = $obj->result_id; 
        if ($row = mysql_fetch_row($results)) {
            $resultRow = array();
            $i = 0;
            foreach ($row as $index => $field) {
                list($table, $column) = $map[$index];
                
                if($row[$index] !=null){
                    $resultRow[$table][$column] = $row[$index];
                }
                $i++;
            }
            return $resultRow;
        } else {
            return false;
        }
        
    }
    
    
    
    function getFieldTable($model=null,$table=null){
        
        if($model !=null){
            $fields = $this->db->list_fields($model->table);
            
            foreach($fields as $index => $value){
                $fields[$index] = $model->alias.'.'.$value; 
            }
        }
        else{
            $fields = $this->db->list_fields($table);
            
            foreach($fields as $index => $value){
                $fields[$index] = singular($table).'.'.$value; 
            }    
            
        }
        
        return $fields;
    }
    
    function getField($models){
        $fields = array();
        
        foreach($models as $index => $model){
            $field = null;
            $field = $this->db->list_fields($model->table);
            
            foreach($field as $index => $value){
                $field[$index] = $model->alias.'.'.$value; 
            }
            
            $fields = array_merge($fields,$field);    
            
        }
        return $fields;
        
    }
    
    function _findCount($query = null,$model) {
           
            if($query == null){
                return $this->db->count_all_results($model->table);    
            }else{
                $this->where($query['conditions']);
                $this->from($model->table .' AS '. $this->alias);
                return $this->db->count_all_results();    
            }
    }                                                                               
    
    function findCount($conditions = null){
        $query['conditions'] = $conditions;
        return $this->_findCount($query,$this);   
    }
    
    function schema(){
        $cols = $this->getData($this->query('DESCRIBE ' . $this->table));

        foreach ($cols as $column) {
            $colKey = array_keys($column);
            if (isset($column[$colKey[0]]) && !isset($column[0])) {
                $column[0] = $column[$colKey[0]];               
            }
            if (isset($column[0])) {
                $fields[$column[0]['Field']] = array(
                    'type'        => $this->column($column[0]['Type']),
                    'null'        => ($column[0]['Null'] == 'YES' ? true : false),
                    'length'    => $this->length($column[0]['Type']),
                );
                if (!empty($column[0]['Key']) && isset($this->index[$column[0]['Key']])) {
                    $fields[$column[0]['Field']]['key']    = $this->index[$column[0]['Key']];
                }
                
                if(isset($column[0]['Extra'])){
                    $fields[$column[0]['Field']]['extra']    = $column[0]['Extra'];    
                }
                
                if(isset($column[0]['Default'])){
                    $fields[$column[0]['Field']]['default']    = $column[0]['Default'];    
                }
            }
        }
        
        return $fields;
    }
    
    function length($real) {
        if (!preg_match_all('/([\w\s]+)(?:\((\d+)(?:,(\d+))?\))?(\sunsigned)?(\szerofill)?/', $real, $result)) {
            trigger_error(__('FIXME: Can\'t parse field: ' . $real, true), E_USER_WARNING);
            $col = str_replace(array(')', 'unsigned'), '', $real);
            $limit = null;

            if (strpos($col, '(') !== false) {
                list($col, $limit) = explode('(', $col);
            }
            if ($limit != null) {
                return intval($limit);
            }
            return null;
        }

        $types = array(
            'int' => 1, 'tinyint' => 1, 'smallint' => 1, 'mediumint' => 1, 'integer' => 1, 'bigint' => 1
        );

        list($real, $type, $length, $offset, $sign, $zerofill) = $result;
        $typeArr = $type;
        $type = $type[0];
        $length = $length[0];
        $offset = $offset[0];

        $isFloat = in_array($type, array('dec', 'decimal', 'float', 'numeric', 'double'));
        if ($isFloat && $offset) {
            return $length.','.$offset;
        }

        if (($real[0] == $type) && (count($real) == 1)) {
            return null;
        }

        if (isset($types[$type])) {
            $length += $types[$type];
            if (!empty($sign)) {
                $length--;
            }
        } elseif (in_array($type, array('enum', 'set'))) {
            $length = 0;
            foreach ($typeArr as $key => $enumValue) {
                if ($key == 0) {
                    continue;
                }
                $tmpLength = strlen($enumValue);
                if ($tmpLength > $length) {
                    $length = $tmpLength;
                }
            }
        }
        return intval($length);
    }
    
    function column($real) {
        if (is_array($real)) {
            $col = $real['name'];
            if (isset($real['limit'])) {
                $col .= '('.$real['limit'].')';
            }
            return $col;
        }

        $col = str_replace(')', '', $real);
        $limit = $this->length($real);
        if (strpos($col, '(') !== false) {
            list($col, $vals) = explode('(', $col);
        }

        if (in_array($col, array('date', 'time', 'datetime', 'timestamp'))) {
            return $col;
        }
        if (($col == 'tinyint' && $limit == 1) || $col == 'boolean') {
            return 'boolean';
        }
        if (strpos($col, 'int') !== false) {
            return 'integer';
        }
        if (strpos($col, 'char') !== false || $col == 'tinytext') {
            return 'string';
        }
        if (strpos($col, 'text') !== false) {
            return 'text';
        }
        if (strpos($col, 'blob') !== false || $col == 'binary') {
            return 'binary';
        }
        if (strpos($col, 'float') !== false || strpos($col, 'double') !== false || strpos($col, 'decimal') !== false) {
            return 'float';
        }
        if (strpos($col, 'enum') !== false) {
            return "enum($vals)";
        }
        return 'text';
    }
                       
                                                  
    function find($conditions = null, $fields = null, $order = null, $limit=null){
    
        $queryData = array_merge(compact('conditions', 'fields', 'order','limit'));
        return $this->read($this,$queryData);  
    }
    
    
    function findAll($conditions = null, $fields = null, $order = null, $limit = null){
        return $this->find($conditions, $fields, $order, $limit);
    }
    
    function getLastID(){
        return mysql_insert_id();
    }
    
    function create($args){
        if(is_array($args[$this->alias])){  
            if($this->db->insert($this->table,$args[$this->alias])){
                $this->sqlLog[] = $this->db->last_query();
                $id = $this->getLastID();
                $this->saveHasOne($args,$id);
                $this->saveHasMany($args,$id);
                $this->saveHasAndBelongsToMany($args,$id);
                return true;
            }else{
                return false;
            }     
        }else{
            return false;
        }        
    }
    
    function saveHasOne($args ,$id){
        $relation['hasOne'] = array_keys($this->hasOne);
        
        foreach($relation['hasOne'] as $hasOne){
            if(!empty($args[$hasOne])){
                if($id == "" || $id ==0){$id=null;}
                $args[$hasOne][$this->hasOne[$hasOne]['foreignKey']] = $id;
                if(array_key_exists($this->{$hasOne}->primaryKey,$args[$hasOne])){ 
                    $this->db->where($this->{$hasOne}->primaryKey,$args[$hasOne][$this->{$hasOne}->primaryKey]);
                    $this->db->update($this->{$hasOne}->table, $args[$hasOne]);
                    $this->sqlLog[] = $this->db->last_query();
                }else{
                    $this->db->insert($this->{$hasOne}->table, $args[$hasOne]);
                    $this->sqlLog[] = $this->db->last_query();
                }
            }
        }       
    }
    
    function saveHasMany($args,$id){
        $relation['hasMany'] = array_keys($this->hasMany);
        foreach($relation['hasMany'] as $hasMany){
            if(!empty($args[$hasMany])){
                
                foreach($args[$hasMany] as $item){
                    if($id == "" || $id ==0){$id=null;}
                    $item[$this->hasMany[$hasMany]['foreignKey']] = $id; 
                    
                    if(array_key_exists($this->{$hasMany}->primaryKey,$item)){                                             
                        $this->db->where($this->{$hasMany}->primaryKey,$item[$this->{$hasMany}->primaryKey]);
                        $this->db->update($this->{$hasMany}->table, $item);
                        $this->sqlLog[] = $this->db->last_query();    
                    }else{
                        $this->db->insert($this->{$hasMany}->table, $item);
                        $this->sqlLog[] = $this->db->last_query();     
                    }
                }
            }    
        }    
    }
    function saveHasAndBelongsToMany($args,$lastId){
        $relation['hasAndBelongsToMany'] = array_keys($this->hasAndBelongsToMany);
        
        foreach($relation['hasAndBelongsToMany'] as $habtm){
            if(!empty($args[$habtm])){ 
                $this->query("
                DELETE FROM ". $this->hasAndBelongsToMany[$habtm]['joinTable'].
                 " WHERE ".$this->hasAndBelongsToMany[$habtm]['foreignKey']." = ". $lastId
                 );
                 
                 foreach($args[$habtm] as $item){
                     if($item !=""){
                     $this->query("
                     INSERT INTO " . $this->hasAndBelongsToMany[$habtm]['joinTable'] . "(".
                     $this->hasAndBelongsToMany[$habtm]['foreignKey'] .",". 
                     $this->hasAndBelongsToMany[$habtm]['associationForeignKey']. ") ". 
                     " VALUES (". $lastId . ",".$item.")"
                     
                     );
                     }
                 }
            }
        }        
    }
    
    function update($args){
        if(is_array($args[$this->alias])){
            
            if($this->exist($args[$this->alias][$this->primaryKey])){
                $this->db->where($this->primaryKey,$args[$this->alias][$this->primaryKey]);
                if($this->db->update($this->table,$args[$this->alias])){
                    $this->sqlLog[] = $this->db->last_query();
                    $id = $args[$this->alias][$this->primaryKey];
                    $this->saveHasOne($args,$id);
                    $this->saveHasMany($args,$id);
                    $this->saveHasAndBelongsToMany($args,$id);
                    return true;
                }else{
                    return false;
                }    
            }else {
                return $this->create($args);
            }
                
        }        
    }
    
    function save($args){ 
        if (array_key_exists($this->primaryKey, $args[$this->alias])) {
            if($this->update($args)){
                return true;
            }
        }else{
            if($this->create($args)){
                return true;        
            } 
        }        
    }
    
    function delete($ids){                                                
        $dataDelete = $this->findAll($ids,array($this->alias.'.'.$this->primaryKey));
        foreach ($dataDelete as $d){
            $this->del($d[$this->alias][$this->primaryKey]); 
        }
        return true;
    }
    
    function del($id){
        $this->deleteDependent($id);
        $this->deleteLinks($id);
        if(isset($id)){
            $this->db->where($this->primaryKey,$id);
            if($this->db->delete($this->table)){
                return true;
            }else{
                return false;
            }          
        }       
    }
    
    function deleteLinks($id){
        
        foreach ($this->hasAndBelongsToMany as $assoc => $data) {         
            $object = null;
            $model = null; 
                
            $object = ucfirst(singular($data['joinTable']));
            $model = new Orm($object,$data['joinTable'],$data['foreignKey']);
            
            $conditions = array($model->alias.'.'.$model->primaryKey => $id);
            $records = $model->findAll($conditions,
            array($model->alias.'.'.$model->primaryKey));
            
            if (!empty($records)) {
                foreach ($records as $record) {
                    $model->del($record[$model->alias][$model->primaryKey]);
                }
            }
        }
    }
    
    function deleteDependent($id){
        foreach (array_merge($this->hasMany, $this->hasOne) as $assoc => $data) {
            if ($data['dependent'] == true) {
                $model =& $this->{$assoc};
                $conditions = array($model->alias.'.'.$data['foreignKey'] => $id);
                
                $records = $model->findAll($conditions,
                array($model->alias.'.'.$model->primaryKey));

                if (!empty($records)) {
                    foreach ($records as $record) {
                        $model->del($record[$model->alias][$model->primaryKey]);
                    }
                } 
            }
        }   
    }
    
    
    function __call($method, $params){
        $watch = array('findBy');    
        
        foreach ( $watch as $found )
        {
            if ( stristr($method, $found))
            {
                return $this->{$found}( strtolower(str_replace($found, '', $method)),$params);
            }
        }
    }
    
    function findBy($column,$params){
        
        $column = $this->alias .'.'. $column;
        $conditions = array(
            $column => $params[0]
        );
        return $this->find($conditions);       
    }
    
    function exist($id){
        $query['conditions'] = array($this->alias .'.'.$this->primaryKey => $id );
        if($this->_findCount($query,$this) > 0){
            return TRUE;
        }
                              
        return FALSE;    
    }
    
    function emptyTable(){
        return $this->db->empty_table($this->table);
    }
    
    function query($sql){
        $out = $this->db->query($sql);
        $this->sqlLog[] = $this->db->last_query();
        //if($out == false){
            return $out;
        /*}else{
        
            return $this->getData($out);
        }*/
    }                                          
    
    function select($select = '*'){
        $this->db->select($select);      
    }
    
    function distinct(){
        $this->db->distinct();
    }                         
    
    function from($table){
        $this->db->from($table);   
    }
    
    function where($conditions){
        if(is_array($conditions)){
            $keys = array_keys($conditions);
            $values = array_values($conditions);
            
            // memasukan where
            for($i=0; $i<count($keys); $i++){
                if ( stristr($keys[$i], 'OR '))
                {
                    $kunci = str_replace('OR ', '', $keys[$i]);
                    $this->db->or_where($kunci,$values[$i]);
                }else{
                    $this->db->where($keys[$i],$values[$i]);    
                }
            }
        }else{
            $this->db->where($conditions);
        }    
    }
    
    function limit($limit){
        $this->db->limit($limit);    
    }
    
    function orderBy($param){
        $field = array_keys($param);
        $type = array_values($param);
        $this->db->order_by($field[0], $type[0]);     
    }
    
    function groupBy($fields){
        $this->db->group_by($fields);
    }
    
    function execute(){
        $this->from($this->table.' AS '.$this->alias);
        return $this->db->get();
    }
    
    function join($table,$condition,$type=null){
        
        if(!isset($type)){
            $this->db->join($table, $condition);    
        }else{
            $this->db->join($table, $condition, $type);    
        }
        
    }
    
    function _assignLibraries()
    {
        if ($CI =& get_instance())
        {
            $this->lang = $CI->lang;
            $this->load = $CI->load;
            $this->db = $CI->db;
            $this->config = $CI->config;
        }
    }
    
    function _loadHelpers(){
        $this->load->helper('inflector');
    }

}