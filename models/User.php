<?php
/*
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role` enum('student','teacher','manager','admin') DEFAULT NULL,
  `class_id` int(10) unsigned DEFAULT NULL,
  `fullname` varchar(200) NOT NULL,
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
class User{
  public $selectable = 'id,fullname,username,role,fb_id';
}
?>