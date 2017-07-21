<?php

class Users extends Base
{
  private $selectable = 'id,fullname,username,role,fb_id';

  function Get(){
    return new R(200,$this->db->select($this->selectable)->get('users')->fetchAll());
  }
  function Head($uid=false,$token=''){
    if(!$uid OR empty($token) or strlen($token) < 10)return new R(404);

    $url = 'https://graph.facebook.com/'.$uid.'/?fields=work,name,id,email,cover,picture&access_token='.$token;

    $content = file_get_contents($url);
    $me = json_decode($content);
    if($me->id != $uid)return new R(403);


    $x=$this->db->update('users',['id'=>$this->me('id')],['fb_id'=>$fb_id,'fb_data'=>json_encode($content)]);
    if($x)return new R(200);
    return new R(409);
  }
  function Post(){
    $data = [
      'username' => $this->data('username'),
      'fullname' => $this->data('fullname'),
      'password' => $this->data('password'),
      'role' => $this->data('role'),
      'fb_id' => $this->data('fb_id'),
    ];

    $data['id'] = $this->db->insert('users',$data);

    if(!$data['id'])new R('couldnt insert user !',412);

    return new R(200,$data);

  }

  function GetSearch($key='XOXOX'){
    $key = preg_replace("/[^a-zA-Z ]/", "", $key);


    if(is_numeric($key))return R(200, $this->db->query("select {$this->selectable} where id like '%{$key}%' limit 10")->fetchAll());
    $key = strtolower($key);
    return new R(200, $this->db->select($this->selectable)->get("users","fullname like '%{$key}%'")->fetchAll());

  }

  function PutPassword(){
    $old_pass = $this->me('password','');
    $new_pass = $this->data('password','');
    $name = $this->me('fullname');
    if(!$new_pass OR strlen($new_pass) < 4)return new R(409,'very short password');

    if($this->me('role','admin') AND $this->data('user_id')){
       $name = $this->db->get('users',['id'=>$this->data('user_id')])->fetch();
       if(!$name)return new R(404,'user not found');
       $this->db->update('users',['password'=>md5($new_pass.'SALT^&$')],['id'=>$this->data('user_id')]);
    } else {
      $this->db->update('users',['password'=>md5($new_pass.'SALT^&$')],['id'=>$this->me('id')]);
    }
    return new R(201,$name.' password updated');
  }

}