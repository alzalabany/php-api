<?php

class blog extends Base
{

  function Get(){
    //,"expire_at >= now()"
    return new R(200,$this->db->select('blog.id as key_id,blog.*')->limit(10)->get('blog')->fetchAll(PDO::FETCH_UNIQUE));
  }
  function Post(){
  	$me = $this->secure('admin');
  	if($me->role !== 'admin')throw new Exception('access denied',409);

    $data = [
      'link'=>$this->data('link',''),
      'body'=>$this->data('body',''),
      'expire_at'=>date('Y-m-d', strtotime("+30 days")),
      'user_id'=>$this->me('id')
    ];
    $data['id'] = $this->db->insert('blog',$data);
    if($data['id'])return new R(200,$data);

    return new R(409,'couldnt create post');
  }
  function Delete($id=0){
  	$me = $this->secure('admin');
  	if($me->role !== 'admin')throw new Exception('access denied',409);

    if(!$id OR $this->db->get('blog',['id'=>$id],1)->rowCount()!==1)return new R(201);

    $this->db->delete('blog',['id'=>$id],1);
    return new R(200);
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