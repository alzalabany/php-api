<?php
/*
CREATE TABLE IF NOT EXISTS `visits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(200) NOT NULL,
  `username` varchar(200) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `password` varchar(45) NOT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('male','female') DEFAULT 'male',
  `info` text DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `rel` varchar(100) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `picture` varchar(200) DEFAULT NULL,
  `last_login` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted` BOOLEAN NULL DEFAULT NULL;,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  FULLTEXT KEY `fullname` (`fullname`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;*/

Class Visit{
  public $day_day;
  public $day_month;
  public $day_year;
  public $note;
  public $ref_dr;
  public $husband_age;
  public $husband_name;
  public $patient_age;
  public $patient_name;
  public $time;
  public $day;
  public $services;
  public $room_id;
  public $id;
  public $db;

  public function __construct($data=null) {
    if($data===null)return;
    if(is_numeric($data))return $this->fetch($data);

    //consume $data;
    foreach ($data as $key => $value) {
      $this[$key] = $value;
    }
  }
  public function not_valid(){}
  public function getErrorString(){}
  public function data(){}
  public function save($db){
    $this->db = $db;
    if($this->id)return $this->update();

    return $this->create();
  }
  private function update(){}
  private function create(){}
}