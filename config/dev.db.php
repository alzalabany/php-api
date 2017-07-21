<?php
class iSchool{
	public $db,
				 $__url= 'http://localhost/ivf/',
				 $__master_password= '1';


	function __construct(){
		$this->db = new MyPDO('ivf','root','');

	    DEFINE('ONESIGNAL_APP_ID','');
	    DEFINE('ONESIGNAL_PASS','');

	}


}
