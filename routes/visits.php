<?php
//require FCPATH.'database/RedBean.php';
require_once FCPATH.'vendor/autoload.php';
use RedBeanPHP\Facade as RB;
RB::setup( 'mysql:host=localhost;dbname=ivf', 'root', '' ); //for both mysql or mariaDB
class visits extends Base
{
  public $slots = ['00:00','00:30','01:00','01:30','02:00','02:30','03:00','03:30','04:00','04:30','05:00','05:30','06:00','06:30','07:00','07:30','08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30','20:00','20:30','21:00','21:30','22:00','22:30','23:00','23:30'];

  
  function Get(){
    if($this->me('role','dr')){
      $this->db->where('ref_id',$this->me('id'))->limit(50);
    }

    return new R(200, $this->db->select('visits.id as _id,visits.*,icsi.*')->join('icsi','icsi.card_id = visits.id','left')->order_by('visits.day,visits.slot,visits.id desc limit 50')->get('visits')->fetchAll(PDO::FETCH_UNIQUE));

  }
  function initCardData($id, $bean='icsi'){
    
    
    return $icsi;
  }
  function GetData($card_id){
    $icsi = RB::findOne('icsi','card_id = ?', [$card_id]);
    if (!$icsi) { 
      $icsi = RB::dispense('icsi'); 
      $icsi->created_at = time();
      $icsi->created_by = $this->me('id');
      $icsi->last_access = time();
      RB::store( $icsi );
    }
    
    $visit = RB::load('visits',$card_id)->export() + $icsi->export();

    return new R(200,$visit);
  }

  function PutData($card_id){
    $this->Put($card_id);

    $icsi = RB::findOne('icsi','card_id = ?', [$card_id]);
    if (!$icsi) { $icsi = RB::dispense('icsi'); }
    $icsi->card_id = $card_id;
    $icsi->import($this->data());
    $icsi->last_update = time();
    RB::store( $icsi );
    
    return new R(
        201,
        RB::load('visits',$card_id)->export() + $icsi->export()
      );
  }

  function Put($card_id){
    $visit = RB::load('visits',$card_id);
    $visit->import($this->data(),"day,slot,type,status,ref_id,user_id,room_id,services,patient,patient_age,husband,husband_age,phone,note,e2");
    RB::store( $visit );

    return new R(201, $visit);
  }


  function GetOfday($day=false){
    $day = date('Y-m-d',(int)$day);
    if(!$day || !$day==='1970-01-01')$day=date('Y-m-d');
    return new R(200, $this->db->get('visits',['day'=>$day])->fetchAll());
  }

  function __format($time,$sufix='00'){
    $time = (int)$time;
    return $time > 9 ? $time.':'.$sufix : '0'.$time.':'.$sufix;
  }
  function GetConfig(){
    return new R(200,
      [
      'extras' => ['imsi','emberyoscope','LAh','vit. all','PGD','basket'],
      'dates'  => [
                    strtotime('+2 days'),
                    strtotime('+3 days'),
                    strtotime('+4 days'),
                    strtotime('+5 days')]
      ]);
  }
  function GetFilter(){
    $data = [
      'day'=>$this->data('day'),
      'patient'=>$this->data('patient'),
      'room_id'=>$this->data('room_id')
    ];
    foreach($data as $key=>$val)if(!$val)unset($data[$key]);


    return new R(200, $this->db->select("id as _id,visits.*")->get('visits',$data)->fetchAll(PDO::FETCH_UNIQUE));
  }
  function Post(){

    $type = $this->data('type','');
    if(!in_array(strtoupper($type), ['ICSI','IUI','THAWING']))
        return new R(412,'type is required');

    $f=["day","slot","type","status","ref_id","user_id","room_id","services","patient","patient_age","husband","husband_age","phone","note","e2"];

    $n = [];

    foreach($this->data() as $var=>$value){
      if( in_array($var, $f) ) $n[$var] = $value;
    }

    if( !array_keys_exists(["day","type","ref_id","patient"], $n) )
      return new R(412,'please fill all required');

    if($type==='ICSI'){
       if(!array_keys_exists(["slot","room_id"],$n)) return new R(412,'please select time');

        if($this->db->select('id')->get('visits',['slot'=>$n['slot'],'day'=>$n['day']])->rowCount())return new R(412,'time already booked chose another please');

        list($t,$min) = (int) explode(':', $n['slot']);
        if($min !== "00" AND $min !== "30")
            throw new Exception("malformated time", 412);

        $o = $this->db->query("
  select rooms.id,rooms.name,rooms.weekend,IFNULL(d.starting_hour,rooms.starting_hour) as starting_hour,
  IFNULL(d.ending_hour,rooms.ending_hour) as ending_hour
  from rooms
  left join day_off as d on (d.room_id = rooms.id  and d.day = \"{$n['day']}\")
  where rooms.id = \"{$n['room_id']}\"
  group by rooms.id limit 1")->fetch();


        if(!$o)throw new Exception("room does not exists", 404);


        if($t > $o['ending_hour'] OR $t < $o['starting_hour']){
          return new R(412,'center is closed at this time');
        }

        //VALIDATE SLOT..
        $time = strtotime($n['day'].' '.$n['slot'].':00');
        $start = strtotime(date('Y-m-d').' +1 day');
        $end = strtotime(date('Y-m-d').' +5 day');
        if($time <= $start)
          throw new Exception("cannot book so soon, you need 24hrs at least", 404);
        if($time >=$end)
          throw new Exception("cannot book so far, maximum 5 days ahead", 404);

      }

    if($this->me('role','dr')){
      $n['ref_id'] = $this->me('id');
    } else {
      if($this->db->select('id')->get('users',['id'=>$n['ref_id']])->rowCount()!==1)
        throw new Exception("dr not found", 404);
    }
    $n['id'] = $this->db->insert('visits',$n);
    return new R(201,$n);
  }

  function GetCheck(){
    return $this->GetAvaliable($this->data('day'),$this->data('room_id'));
  }

  function GetFree($day=false){
    $daytime = (int)$day ? $day : time();
    $day = date('Y-m-d',$daytime);
    $rooms = $this->db->select('id,starting_hour,ending_hour,weekend,size,name')
                      ->get('rooms')->fetchAll();
    $holidays = $this->db->select('room_id,day,starting_hour,ending_hour,size')
                         ->get('day_off',['day'=>$day])->fetchAll();

    $visits = $this->db->select('slot,room_id')->get('visits',['day'=>$day])->fetchAll();
    
    $data=[ 'rooms'=>[],
            'holidays'=>[],
            'slots'=>[],
            'busy'=>[],
            'currentDay'=>$day,
            'weekday'=>date('w',$daytime),
            ];
    foreach ($visits as $row) $data['busy'][$row->room_id][$row->slot]=true;
    foreach ($rooms as $row) $data['rooms'][$row->id]=$row;
    foreach($holidays as $day){
      $data['rooms'][$day->room_id]->starting_hour = $day->starting_hour;
      $data['rooms'][$day->room_id]->ending_hour = $day->ending_hour;
      $data['holidays'][$day->room_id][$day->day] = $day;
    }

    foreach($data['rooms'] as $room){
      if(!isset($data['busy'][$room->id]))$data['busy'][$room->id]=[];

      if( ($room->starting_hour >= $room->ending_hour) OR 
          ($room->starting_hour==0 AND $room->starting_hour==0) OR
          stripos($room->weekend, $data['weekday']) !== false
          )continue;

      $data['slots'][$room->id] = $this->_getRanges($room->starting_hour, $room->ending_hour,$room->size);
      $data['rooms'][$room->id]->busyCount  = count($data['busy'][$room->id]);
      $data['rooms'][$room->id]->slotsCount = count($data['slots'][$room->id]);
    }

    //clear busy slots from slots;
    foreach($data['slots'] as $room_id=>$slots)$data['slots'][$room_id] = array_values(array_filter(
        $data['slots'][$room_id],
        function($time) use($room_id,$data){return empty($data['busy'][$room_id][$time]);}
      ));

    return $data;
  }

  function _getRanges($start=8,$end=15,$time=30) {
    $formatter = function ($time) {
      return date('H:i', $time);
    };
    $start = strtotime($start.':00');
    $end = strtotime($end.':00');
    $halfHourSteps = array_map($formatter,range($start, $end-($time*60), $time*60));
    return $halfHourSteps;
  }


  function GetAvaliable($day=false,$room_id=false){
    $day = strtotime($day);
    if(!$day)$day = strtotime('@'.$day);
    if(!$day)$day = time();
    $day = date('Y-m-d',$day);
    $rooms = $this->db->select('id,starting_hour,ending_hour,weekend,size,name')
                      ->where('id >=','1')
                      ->get('rooms')->fetchAll();

    $source = $this->slots;
    $map=[];
    foreach($rooms as $key=>$room){
      $startIndex = 0;
      $startValue = $this->__format($rooms[$key]->starting_hour);
      $endValue = $this->__format($rooms[$key]->ending_hour);
      $slots = [];
      foreach ( $source as $index => $value ) {
          if ( $value === $startValue ) {
              $startIndex = $index;
          } else
          if ( $value === $endValue ) {
              $slots = array_slice($source, $startIndex, $index - $startIndex + 1);
          }
      }
      $map[$room->id] = array_combine($slots,$slots);
    }

    foreach($this->db->select('slot,room_id')->get("visits",['day'=>$day])->fetchAll() as $visit)$map[$visit->room_id][$visit->slot] = false;

    $final = [];

    foreach($map as $m)foreach($m as $time=>$val)if($val)$final[]=$time;

    $final = array_unique($final);
    sort($final);

    return new R(200,$final);

  }
}