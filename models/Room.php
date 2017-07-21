<?php

class Room{
  public $id = 0;
  public $starting_hour = 0;
  public $ending_hour = 0;
  public $name = 'All day';
  public $weekend = '';

  public function __construct($data=null) {
    if(empty($data))return;
    $raw = (array)$data;
    if(array_key_exists('id', $raw)) $this->id = $data['id'];

    if(array_key_exists('starting_hour', $raw)) $this->starting_hour = (int) $data['starting_hour'];

    if(array_key_exists('ending_hour', $raw)) $this->ending_hour = (int) $data['ending_hour'];

    if(array_key_exists('name', $raw)) $this->name = (string) $data['name'];

    if(array_key_exists('weekend', $raw)) $this->weekend = is_array($data['weekend']) ? implode(',', $data['weekend']) : (string) $data['weekend'];
  }

  function not_valid(){

    if(!is_numeric($this->starting_hour) OR
       !is_numeric($this->ending_hour) OR
       (int) $this->starting_hour > 23 OR
       (int) $this->ending_hour > 23   OR
       (int) $this->starting_hour < 0  OR
       (int) $this->ending_hour < 0.   OR
       (int) $this->starting_hour > $this->ending_hour
      )return 'working hours not valid';

    $weekend = explode(',', $this->weekend);
    if(empty($weekend))return false;

    foreach($weekend as $val)if(!is_numeric($val) OR (int)$val<0 OR (int)$val>6)return empty($val) ? false : 'one of weekend is not a valid weekday '.$val.'.';

    return false;
  }
}
//week_end hasmany