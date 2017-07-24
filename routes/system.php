<?php

class system extends Base
{


  function GetDoctors(){
    return new R(200,$this->db->select('id,fullname,role')->get('users',['role'=>'dr'])->fetchAll());
  }
  function GetSettings(){
    $data = [
      'blog'    => $this->db->limit(10)->get('blog',['expire_at <'=>'#NOW()'])->fetchAll(),//,
      'rooms' => [],
      'users' => [],
    ];

    foreach($this->GetRooms()->data as $row){
      $row->weekend = explode(',', $row->weekend);
      $data['rooms'][$row->id] = $row;
      $data['rooms'][$row->id]->interval = $data['rooms'][$row->id]->size;
    }

    $data['rooms'] = array_values($data['rooms']);

    foreach($this->db->get('day_off')->fetchAll() as $day){
      @$data['rooms'][$day->room_id]->holidays[$day->day] = $day;
    }

    if( $this->me('role','dr') ){
       $data['users'][$this->me('id')] = $this->me();
    } else {
      foreach($this->db->select('id,fullname,role')->get('users')->fetchAll() as $row){
        $data['users'][$row->id] = $row;
      }  
    }
    $data['users'][$this->me('id')] = $this->me();

    return new R(200,$data);
  }

  function GetRooms($id=false){
    if($id)$this->db->where('id',$id);
    return new R(200,$this->db->get('rooms')->fetchAll());
  }
  function test($a,$b){
    foreach($b as $n)if(empty($a[$n]))return false;
    return true;
  }
  function PostRoom($id=false){
    $fill = ["name","starting_hour","ending_hour","blocks_count","weekend"];

    $n = [];
    foreach($this->data() as $var=>$value){
      if( in_array($var, $fill) ) $n[$var] = $value;
    }
    if( !$this->test($n,["name","starting_hour","ending_hour"]) )return new R(412,'name, and hours required');

    $n["blocks_count"] = $n["ending_hour"]-$n["starting_hour"];

    if(empty($n['weekend']))$n['weekend']='0,6';

    $n['id'] = $this->db->insert('rooms',$n);

    if(!empty($n['id']))return new R(201,$n);

    new R(500);
  }

}


/*
function GetStores($id=false){
    return new R(200,[]);
  }
  function GetStoreItems($store_id=false){
    $store = $this->db->get('stores',['id'=>$store_id])->fetch();
    if(!$store)throw new Exception("Error Processing Request", 412);
    $arr = $this->db->get('store_items',['store_id'=>$store_id])->fetchAll();
    if(empty($arr))$arr =[];
    return new R(200,$arr);
  }
  function GetHardware($store_id=false){
    $store = $this->db->get('stores',['id'=>$store_id])->fetch();
    if(!$store)throw new Exception("Error Processing Request", 412);

    $r = $this->db->get('hardware',['store_id'=>$store_id])->fetchAll();

    if(!$r)$r=[];


    return new R(200,$r);
  }
  function PostStore(){
    $data = [
    'name'=>$this->data('name',true,true),
    'user_id'=>$this->me('id')
    ];
    $id = $this->db->insert('stores',$data);
    return new R(200,$this->db->get('stores',['id'=>$id])->fetch());
  }
  function PutStores($id=false){
    $row = $this->db->get('stores',['id'=>$id])->fetch();

    if(!$row OR $row->id !== $id)throw new Exception("Error Processing Request", 412);

    $row->name = $this->data('name',true,true);

    $this->db->update('stores',(array)$row,['id'=>$id]);
    return new R(200,$row);
  }

  function GetVendors(){
    return new R(200,$this->db->get('vendors')->fetchAll());
  }
  function PostVendor(){
    $data = [
    'name'=>$this->data('name',true,true),
    'is_local'=>$this->data('is_local') ? 1:0,
    ];
    $id = $this->db->insert('vendors',$data);
    return new R(200,$this->db->get('vendors',['id'=>$id])->fetch());
  }

  function GetItems(){
    return new R(200,$this->db->get('items')->fetchAll());
  }
  function PutItem($id=0){
    $item = $this->db->get('items',['id'=>$id])->fetch();
    if(!$item || !$item->id)throw new Exception("Error Processing Request, missing vendor", 412);

    $tags = $this->data('tags','',true);
    $tags = is_array($tags) ? $tags : explode(',', $tags);
    $tags = array_map(function($tag){return strtolower(trim($tag));}, $tags);
    $tags = implode(',', $tags);

    $this->db->update('items',['tags'=>$tags],['id'=>$id]);
    return new R(200);

  }
  function DeleteItem($id=0){
    $item = $this->db->get('items',['id'=>$id])->fetch();
    if(!$item || !$item->id)throw new Exception("Error Processing Request, missing vendor", 412);
    $this->db->delete('items',['id'=>$id]);
    return new R(200);
  }
  function PostItem(){
    $data = [
    'name'=>$this->data('name',true,true),
    'type'=>$this->data('type',true,true),
    'tags'=>$this->data('tags',''),
    'vendor_id'=>(int) $this->data('vendor_id',0,true),
    'size'=>(int)$this->data('size',0),
    'unit'=>$this->data('size',''),
    'power'=>(int)$this->data('power',0),
    'description'=>$this->data('description',0),
    'model_year'=>(int)$this->data('model_year',date('Y')),
    'user_id'=>$this->me('id')
    ];
    $vendor = $this->db->get('vendors',['id'=>$data['vendor_id']])->fetch();
    if(!$vendor || $vendor->id !== $data['vendor_id']) throw new Exception("Error Processing Request, missing vendor", 412);

    $id = $this->db->insert('items',$data);
    return new R(200,$this->db->get('items',['id'=>$id])->fetch());
  }
*/