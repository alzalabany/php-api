<?php
/**
 * Global functions are used by Base::want to validate array values;
 */
function min5($key){
  return (is_string($key) AND strlen($key) > 4);
}
class Base extends iSchool{
	public $__data=[], $user;

  function __construct() {
    parent::__construct(); // now wer have $this->db;;

    /////////////////
    ///Load Post Data
    //////////////////
    $this->__data = []; ///POST DATA
    if(HTTP_METHOD === 'GET'){
      parse_str($_SERVER['QUERY_STRING'],$this->__data);
      if(empty($this->__data)) $this->__data = (array) ($_GET OR []);
    }else{
      $this->__data = json_decode(file_get_contents("php://input") , true);
      if (empty($this->__data) AND !empty($_POST)) $this->__data = $_POST;
    }
    if(empty($this->__data))$this->__data=[];
  }


  function getUserFromHeaders($refreshToken=false){
      if (!UID OR !TOKEN)return 'please login again';

      $this->user = $this->db
                      ->where('token',TOKEN)
                      ->where('id',UID)
                      ->select('users.*')
                      ->limit(1)
                      ->get('users')
                      ->fetch();


      if ( !$this->me('id') )return 'token expired, please login again';
      unset($this->user->password);
      if($refreshToken)$this->user->token = md5(uniqid()).'.'.$user->id;

      $this->db->update('users',
                      ['last_login'=>'#NOW()'],
                      ['token'=>$this->user->token,'id'=>UID],
                      1);
      header('Authorization: Bearer '.$this->user->token);
      return true;
  }

  /**
   * Create $this->user and populate it with using UID and TOKEN
   * @param  boolean $role if provided will validate user is on this role;
   * @return Object        $user object
   */
  function secure($role=false){
  	if($this->user){
  		if(!$this->me('role',$role) and $role !== false)
        throw new Exception('access denied for'.$this->user->role,403);

  		return $this->me();
  	}

    $auth = $this->getUserFromHeaders(false);

    if($auth===false)throw new Exception('access denied for'.$auth,403);



    if ( !$this->me('role',$role) )
        throw new Exception('access restricted to '.$role.' only',403);

  	return $this->me();
  }



	function data($key=false,$default=false,$required=false){
		if(!$key){
      if($required===false)return $this->__data;
      throw new Exception('invalide need call',500);
    }

    if(is_array($key)){
      $data=[];
      foreach ($key as $value) {
        $data[$value] = $this->iNeed($value);
      }
      return $data;
    }

    if(!array_key_exists($key,$this->__data) AND $required===true)
			throw new Exception('Please provide a valid'.$key.' is required',409);

    return array_key_exists($key,$this->__data) ? $this->__data[$key] : $default;
	}

  function me($f = null, $cmp = null)
  {
    if (empty($this->user) or !isset($this->user->id)) return false;
    if (empty($f)) return $this->user;

    // of no field requested, send the whole object

    if(is_array($f)){
      $r=[];
      foreach($f as $v)$r[]=$this->user->$v;
      return $r;
    }

    if (!isset($this->user->$f)) return null; //die('you asked for a field that doesnot exists' . $f);
    if (empty($cmp)) return $this->user->$f;
    if (is_array($cmp)) return in_array($this->user->$f, $cmp);
    else return ($this->user->$f == $cmp);
  }

  /**
   * iWant take an array of data, and rules and output transformation on data;
   * Usage: $ret = iWant($data,
   *                     [  'title as heading'=>'string',
   *                        'is_event'=>'bool',
   *                        'time_created as time_posted'=>'time'
   *                     ],
   *                     ['time']);
   * @param  [array] $data     [array contain all data]
   * @param  [array] $rules    [array contain rules for each key that i want to extract in output]
   * @param  [array]  $required [array of required keys, they must exist]
   * @return [string|array]  return string in case of error, or array of data after transformation.
   */


}


class API extends Base{
	function __construct(){
		parent::__construct();
		$this->secure();
	}
}
