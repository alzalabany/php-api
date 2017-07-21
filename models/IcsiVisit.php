<?php

/*
`injection_time` varchar(200) NULL,
`incubation_media` varchar(200) NULL,
`incubator` varchar(200) NULL,
`incu_lah_indication` varchar(200) NULL,
`incu_lah_num` varchar(22) NULL,
`incu_lah_grade` varchar(22) NULL,

`incu_eggs_count` varchar(200) NULL,
`incu_eggs_data` varchar(200) NULL,

`transfer_day` varchar(4) NULL,
`transfer_num` varchar(4) NULL,
`transfer_catheter` varchar(100) NULL,
`transfer_type` varchar(4) NULL,
`transfer_dr` varchar(200) NULL,
`transfer_embryo` varchar(111) NULL,
`transfer_witness` varchar(111) NULL,
`transfer_notes` text NULL,
*/

Class ICSI extends Visit{
  public $hcg_time   = '';
  
  // history
  public $Hepatitis = '';
  public $indication = '';
  public $medical_history = '';
  public $prev_art = '';
  public $protocol = '';
  public $st_days = '';
  public $final_e2 = '';
  public $final_p4 = '';
  // Medical
  public $hmg = '';
  public $fsh = '';
  public $rec_fsh = '';
  public $hcg = '';
  public $hmg_count = '';
  public $fsh_count = '';
  public $rec_fsh_count = '';
  public $hcg_count = '';

  //extra services
  public $et_day = '';
  public $oocyte_basket = '';
  public $emberyo_basket = '';
  public $IMSI = '';
  public $emberyoscope = '';
  public $lah = '';
  public $vit_all = '';
  public $PGD = '';
  //andrology
  public $emberyologist = '';
  public $prep_method = '';
  public $pre_vol = '';
  public $pre_conc = '';
  public $pre_mot = '';
  public $pre_rp = '';
  public $pre_abn = '';
  public $post_vol = '';
  public $post_conc = '';
  public $post_mot = '';
  public $post_rp = '';
  public $post_abn = '';
  //witnesses
  public $or_witness_embr = '';
  public $or_witness_name = '';
  public $or_witness_no = '';
  public $or_witness_time = '';
  public $or_witness_media = '';
  public $den_witness_embr = '';
  public $den_witness_name = '';
  public $den_witness_no = '';
  public $den_witness_time = '';
  public $den_witness_media = '';
  public $sperm_witness_embr = '';
  public $sperm_witness_name = '';
  public $micro_witness_embr = '';
  public $micro_witness_name = '';
  //result
  public $oocyte_or = '';
  public $oocyte_intact = '';
  public $oocyte_m2 = '';
  public $oocyte_m1 = '';
  public $oocyte_gv = '';
  public $oocyte_injected = '';
  public $oocyte_fert = '';
  public $embryo_d1 = '';
  public $embryo_d3 = '';
  public $embryo_d3a = '';
  public $embryo_d5 = '';
  public $embryo_et = '';
  public $embryo_vit = '';
  public $embryo_fert = '';
  public $is_pregnant = null;
  public $injection_time = null;
  public $incubation_media = '';
  public $incubator = '';
  public $incu_lah_indication = '';
  public $incu_lah_num = '';
  public $incu_lah_grade = '';

  public $incu_eggs_count = 0;  
  public $incu_eggs_data = 0;

  public $transfer_day = 0;  
  public $transfer_num = 0;  
  public $transfer_catheter = 0;  
  public $transfer_type = '';//E or D
  public $transfer_dr = '';
  public $transfer_embryo = '';
  public $transfer_witness = '';
  public $transfer_notes = '';

  public function __construct($data=null) {
    parent::__construct($data);

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

}
