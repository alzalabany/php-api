<?php
/**
 * USAGE:
 * $db = new MyPDO('school','root','');
 * CHINNED: select,where,from,limit
 * API :
 *   get($tbl,$id||[col=>val],PDO::DEFAULTMODE) ===> return $statement or false
 *   clear() == > reset query builder
 */
class MyPDO{
  private $db;
  private $params = [];
  private $joins = [];
  private $table = '';
  private $select;
  private $where;
  private $limit;
  private $group;
  private $order;


  function __construct($db,$u,$p=''){
    $this->db = new PDO('mysql:host=localhost;dbname='.$db.';charset=utf8mb4', $u, $p,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                         PDO::ATTR_EMULATE_PREPARES => false,
                         PDO::MYSQL_ATTR_INIT_COMMAND =>"SET time_zone = '+02:00'"]
                       );
  }
  /**
   * Update a record or more with one or more values
   * @param  [type]  $tbl  table name
   * @param  array  $data  prefix with # if you want to bind NOW() or blog.id etc..
   * @param  [type]  $where condition simple key = val
   * @param  integer $limit [description]
   * @return [type]         [description]
   */
  function proccess($k,$val,&$params,$suffix=''){
    $arr = preg_split("/([=!<>]+)(.*)/", $k ,2, PREG_SPLIT_DELIM_CAPTURE| PREG_SPLIT_NO_EMPTY);
    $key = $arr[0];
    $pkey = ':'.preg_replace("/[^A-Za-z0-9_]/", '',$key);
    $op = count($arr)===2 ? $arr[1] : '=';
    if($val[0]==='#'){
      return " {$key} {$op} ".trim($val,'#').$suffix;
    } else {
      $params[$pkey] = $val;
      return " {$key} {$op} {$pkey}".$suffix;
    }
  }
  function update($tbl,$data,$where,$limit=1){
    foreach ($data as $key => $value) {
      if(isset($where->$key) and $where->$key !== $value)throw new Exception('where cannot overwrite other where data',500);
    }
    $params = [];
    $sql = "update {$tbl} set ";
    foreach ($data as $key => $value){
      $sql.= $this->proccess($key,$value,$params,' ,');
    }
    $sql = trim($sql,',').' WHERE ';
    foreach ($where as $key => $value){
      $sql.= $this->proccess($key,$value,$params,' AND');
    }
    $sql = implode(' AND',array_filter(explode(' AND',trim($sql))));
    if($limit)$sql .= ' LIMIT '.$limit;
    // throw new Exception(JSON_encode(),500);
    file_put_contents('./db.log',
                      PHP_EOL.'UPDATE::'.
                      JSON_encode(['data'=>$params,'sql'=>$sql]),
                      FILE_APPEND | LOCK_EX);
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
  }
  function insert($tbl,$data,$onDup=''){
    if(empty($data))throw new Exception('cannot insert empty data',500);
    $data = (array) $data; //in case it was object;

    if( array_key_exists(0,$data) ){
      $keys = array_keys($data[0]);
    }else{
      $keys = array_keys($data);
      $data = [$data];
    }

    $params = array_map(function($b){return ':'.$b;},$keys);
    $placeholder = array_combine($params, array_fill(0,count($params),null) );

    // (optional) setup the ON DUPLICATE column names
    $onDup = empty($onDup) ? '': 'ON DUPLICATE KEY UPDATE '.$onDup;

    $sql = "INSERT INTO {$tbl}(".implode(',',$keys).") VALUES (".implode(',',$params).") $onDup";

    file_put_contents('./db.log',
                      PHP_EOL.'INSERT::'.
                      JSON_encode(['sql'=>$sql,'placeholder'=>$placeholder,'data'=>$data]),
                      FILE_APPEND | LOCK_EX);
    try{
      $this->db->beginTransaction();
      $stmt = $this->db->prepare($sql);

      foreach ($placeholder as $key => $value) {
        $stmt->bindParam($key,$placeholder[$key]);
      }


      foreach($data as $row){
          foreach ($row as $key => $value) {
            $placeholder[':'.$key] = $value;
          }
          $stmt->execute();
      }
      $last_id = $this->db->lastInsertId();
      $this->db->commit();
      return $last_id;
    }catch(PDOException $e){
      $this->db->rollBack();
      throw new Exception($e->getMessage(),500);
    }

  }
  function clear(){
    $this->group = null;
    $this->params=[];
    $this->limit = null;
    $this->select = '';
    $this->where = [];
    $this->joins = [];
    $this->table = '';
    $this->order = '';
    return $this;
  }
  //return combiled statement from current query
  function sql(){
    $q = 'SELECT '.(empty($this->select) ? '*':$this->select);
    $q.= ' FROM '.$this->table;

    if(!empty($this->joins))
      $q.=' '.implode(' ',$this->joins);

    if(!empty($this->where))
      $q.= ' WHERE ('.implode(') AND (',$this->where).') ';

    if(!empty($this->group))
      $q.= ' GROUP BY '.$this->group;

    if(!empty($this->order))
      $q.= ' ORDER BY '.$this->order;

    if(!empty($this->limit))
      $q.= ' LIMIT '.$this->limit;


    return $q;
  }
  function pdo(){return $this->db;}
  function from($q){$this->table = $q;return $this;}
  function limit($q){$this->limit = $q;return $this;}
  function group_by($str){$this->group = $str;return $this;}
  function order_by($str){$this->order = $str;return $this;}
  function delete($tbl,$where,$limit=1){
    $this->table = $tbl;
    $this->select = '  ';
    $this->where($where);
    $this->limit = $limit;
    $q = str_replace('SELECT ','DELETE ',$this->sql());
    file_put_contents('./db.log',
                      PHP_EOL.'DELETE::'.
                      JSON_encode(['sql'=>$q,'params'=>$this->params]),
                      FILE_APPEND | LOCK_EX);
    try{
      $st = $this->db->prepare($q);
      $st->execute($this->params);
    }catch(PDOException $e){
      throw new Exception($e->getMessage(),500);
    }

    $this->clear();
    return $st;
  }
  function join($tbl,$on,$mode=""){
    $q = $mode.' JOIN '.$tbl;

    $q.= strpos($on,'(')===false ? " on ({$on})":" on {$on}";

    $this->joins[] = $q;
    return $this;
  }

  function select($q=''){
    if($this->select){
      $this->select .=','.(is_array($q)?implode(',',$q):$q);
    }else{
      $this->select = is_array($q)?implode(',',$q):$q;
    }

    $this->select = trim($this->select,',');

    return $this;
  }
  function where_in($key,$arr){
    $values = [];
    foreach ($arr as $value) {
        $values[] = (int) $value;
    }
    $this->where[] = "{$key} in (".implode(',',$values).")";
    return $this;
  }
  /**
   * [where description]
   * usage1: [ [or=>['a!='=>'c']],
   *            and=>['d>'=>'#NOW()','f'=>1],
   *            x=>null ];
   *         @return (a!='c') and (d>now() AND f=1) and x is null
   *
   * @param  [type]  $key [description]
   * @param  boolean $val [description]
   * @return [type]       [description]
   */
  function where($key,$val=false){
    if ( is_string($key) ){
      if($val===false){
        $this->where[] = $key;
        return $this;
      }
      if($val===null){
        $this->where[] = "$key is null";
        return $this;
      }

      $this->where[] = $this->proccess($key,$val,$this->params);

      return $this;
    }

    if( is_array($key) ){
       foreach ($key as $name => $val) {
          if($name==='OR' or $name==='AND'){
            $or = [];
            foreach ($val as $sub=>$sval){
              $or[]= $this->proccess($sub,$sval,$this->params);
            }
            $this->where[] = implode(' '.$name.' ',$or);
          }else{
            $this->where[] = ($val===null) ?  "{$name} is null" :
                                              $this->proccess($name,$val,$this->params);
          }
       }
    }

    return $this;
  }

  function query($query){
    file_put_contents('./db.log',
                      PHP_EOL.'QUERY::'.
                      JSON_encode(['sql'=>$query]),
                      FILE_APPEND | LOCK_EX);
    return $this->db->query($query);
  }

  //return statement
  function get($tbl=false,$id=false,$mode=PDO::FETCH_OBJ){
    if($tbl)$this->from($tbl);
    if(is_array($id) OR (is_string($id) AND strlen($id) > 2 AND !is_numeric($id)))$this->where($id);
    if(is_numeric($id))$this->where(['id'=>$id]);
    


    $q=$this->sql();
    file_put_contents('./db.log',
                      PHP_EOL.'GET::'.
                      JSON_encode(['sql'=>$q,'data'=>$this->params]),
                      FILE_APPEND | LOCK_EX);
    try{
      $st = $this->db->prepare($q);
      $st->execute($this->params);
      $st->setFetchMode($mode);
    }catch(PDOException $e){
      throw new Exception($e->getMessage(),500);
    }

    $this->clear();
    return $st;
  }

}
