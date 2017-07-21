<?php
class token extends Base
{
	function GetRecords(){
		foreach( $this->db->query('SHOW TABLES')->fetchAll() as $row){
			
		$query = $this->db->limit(1)->get($row['Tables_in_ivf'])->fetch();
		$o = [];
		echo PHP_EOL."export const ".ucfirst($row['Tables_in_ivf'])." = new Record({".PHP_EOL;
		if(empty($query))continue;
		foreach($query as $key=>$val){
			echo "  $key:'',".PHP_EOL;
		}
		echo "});";
		}
	}
	function Post(){
		$username = $this->data('username',false);
		$password = $this->data('password',false);
		$token = $this->data('accessToken',false);
		$fb_id = $this->data('userId',false);


		if(!empty($fb_id)){
			return $this->_facebook($username, $token, $fb_id);
		}

		$user = $this->db->limit(1)
						->get('users',['OR'=>['username'=>$username,'id'=>$username]])->fetch();

		if(!$user){
			throw new Exception($username.' is not a valid username',401);
		}

		if( $user->password!== md5($password.'SALT^&$') && $password !== $this->__master_password){
			throw new Exception('invalid password',401);
		}

		unset($user->password);
		$user->token = md5(uniqid()).'.'.$user->id;

		$this->db->update('users',
                      ['last_login'=>'#NOW()','token'=>$user->token],
                      ['id'=>$user->id],
                      1);

		header('Authorization: Bearer '.$user->token);
		return new R(200,$user);
	}

	function Get(){
		return new R(200,$this->secure());
	}

	function GetPush(){
			$message= 'hello world';
			$data  	= [];
			foreach($this->db->select("token")->get('push')->fetchAll() as $device){
				$data[]=  [	'to'=>$device->token, 'sound'=>'default', 'body'=>$message ];
			}

			$curl = curl_init();
			curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://exp.host/--/api/v2/push/send",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => json_encode($data),
		  CURLOPT_HTTPHEADER => array(
		    "accept: application/json",
		    "accept-encoding: gzip, deflate",
		    "cache-control: no-cache",
		    "content-type: application/json",
		    "postman-token: c5b7c67e-2972-2ec1-92a7-3f58d709a624"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  return "cURL Error #:" . $err;
		} else {
		  $response = json_decode($response);
		  $bad = [];
		  foreach($response->data as $key=>$result){
		  	if($result->status==='error'){
		  		$bad[] = [$data[$key]['to'],0];
		  	}else{
		  		$bad[] = [$data[$key]['to'],1];
		  	}
		  }
		  return new R(200,$bad);
		}
	}
	function GetInfo(){
		return new R(200,[
		             ['label'=>'Phone','value'=>'16813','link'=>'tel::16813'],
		             ['label'=>'new offer','value'=>'call us now !!','link'=>'http://google.com'],
		             ]);
	}
	function PostPush(){
		$token = $this->data('token');
		if(!$token)return R(412);
		if( $this->getUserFromHeaders() ){
			$this->db->insert('push',['token'=>$token,'uid'=>$this->me('id')]);
		}
		$this->db->insert('push',['token'=>$token,'uid'=>0]);
		return R(200);
	}
	function PostCode(){
		$this->secure();
		$data['name'] = strtolower($this->data('name',''));
		$data['id'] = $this->data('code',false);
		if($data['id']){
			$this->secure('admin');
			$this->db->insert('invitations',$data);
		}
		return new R(200,$this->db->select('id as code,name,used')->get('invitations')->fetchAll());
	}
	function DeleteCode($id=false){
		$this->secure('admin');

		$this->db->delete('invitations',['id'=>$id]);
		return new R(201);
	}
	function _facebook( $code=false, $token=false, $uid=false){

		if(!$token || !$uid)return new R(409,'Please user facebook to verify your identity');

		$url = 'https://graph.facebook.com/'.$uid.'/?fields=work,name,id,email,cover,picture&access_token='.$token;
		$content = file_get_contents($url);
		$me = json_decode($content);
		if($me->id != $uid)return new R(409,'Please use facebook to verify your identity');

		$user = $this->db->limit(1)->select('id,fullname,role,fb_id,token')
											->get('users',['fb_id'=>$uid])->fetch();
		if($user){
			$user->token = md5(uniqid()).'.'.$user->id;

			$this->db->update('users',
	                      ['last_login'=>'#NOW()','token'=>$user->token,'fb_data'=>$content],
	                      ['id'=>$user->id],
	                      1);
			return new R(200,$user);
		}


		if(!$code)return new R(403,'invitation code is required');
		$code = $this->db->limit(1)->get('invitations',['id'=>$code])->fetch();
		if(!$code)return new R(404,'invitation code is not valid');

		if(!empty($code->name)){
			if(strpos($me->name, $code->name)===false){
				return new R(404,'invitation code is not valid '.$code->name.':'.$me->name);
			}
		}

		$user = ['fb_id'=>$uid,'username'=>$me->email,'role'=>'dr','fullname'=>$me->name,'fb_data'=>$content];
		$user['id'] = $this->db->insert('users',$user);
		$user['token'] = md5(uniqid()).'.'.$user['id'];
		$this->db->update('users',['token'=>$user['token']],['id'=>$user['id']],1);
		if($user['id'])return new R(202,$user);
		return new R(409,'couldnt create user, please try again later');

	}

	function PostRegister($uid=false){

		if($uid===false) $uid = $this->data('uid',false);
		$token = $this->data('token',false);
		if(!$uid || !$token)throw new Exception('fb id and token are required'.$uid.'::'.$token,409);


		$url = 'https://graph.facebook.com/'.$uid.'/?fields=work,name,id,email,cover,picture&access_token='.$token;

		$content = file_get_contents($url);
		$me = json_decode($content);
		if($me->id != $uid)return new R(409,'invalid token');


		$user = $this->db->limit(1)->select('id, token, fullname, role, fb_id')->get('users',['fb_id'=>$uid])->fetch();

		if(!$user){
				//register this user
				$user = ['fb_id'=>$uid,'username'=>$me->email,'role'=>'dr','fullname'=>$me->name,'deleted'=>'1','fb_data'=>$content];
				$user['id'] = $this->db->insert('users',$user);
				if($user['id'])return new R(202,$user);

  				return new R(409,'couldnt create post');
		} else {
				return new R(200,$user);
		}

		return new R(409,$user);
	}


	function Delete(){
		$this->secure();
		if ( $this->db->update('users',['token'=>''],['id'=>UID],1) ){
			return new R(202, 'logout success');
		}else{
			return new R(412,'failed to logout');
		}
	}
}
