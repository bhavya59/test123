<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

include_once('class-phpmailer.php');
class Hotel extends CI_Controller {

    protected $paymenturl = "https://www.billdesk.com/pgidsk/PGIMerchantPayment";
    protected $merchantid = 'DOLPHINHTL';
    protected $securityid = 'dolphinhtl';
    protected $password = 'K5ea5e00Y5bO';
    protected $na = 'NA';

    function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(array('url_helper', 'form', 'html'));
        $this->load->model('TA_Booking_Model');
        $this->load->model('UserModel');
        $this->load->library('form_validation');

        $sessiondata = $this->session->userdata('userdetails');
        $user_id = $sessiondata['ta_user_id'];
        $data['user'] = $this->UserModel->getUser_by_id($user_id);
        $this->session->set_userdata('user', $data);
        $data['status_master'] = $this->UserModel->get_status_master();
        $data['htl'] = $this->TA_Booking_Model->getHotels();
        $this->load->vars($data);
    }

    public function index() {

//        $this->load->view("header");
//        print_r($_SESSION);die;
        $header = $this->session->userdata('getheader');
        $this->load->view($header);
        //load the hotels from master
        $data['hotels'] = $this->TA_Booking_Model->getHotels();
        //get the package types
        $args = func_get_args();
        $this->load->view("TA_Booking/hotel", $data);
//        $this->load->view("footer");
        $this->load->view("footer");
    }

    public function getPackageTypes() {
        $hotel_id = $_REQUEST['hotel_id'];
        $data = $this->TA_Booking_Model->getPackageTypes($hotel_id);
        echo json_encode($data);
        //print_r($data);
    }

    /* private function getcatg($hotel, $category, $val = 0) {
      return $this->Booking_model->getcategory($hotel, $category, $val);
      } */

    public function hotels_list($val = 0) {
        $header = $this->session->userdata('getheader');
        $this->load->view($header);
        //here we have hotelid and category id 
        //get the packages based on them
        $sent = $this->input->post();
	
	if($sent['packagetype'] == 'H' && $sent['fromdate'] =='2019-12-30' || $sent['packagetype'] == 'H' && $sent['fromdate'] == '2019-12-31'){
            $url = base_url() . 'hotel/index';
             echo "<script>window.location.href = '$url';</script>";exit;
        }

        if (empty($sent)) {
            redirect(base_url() . 'hotel/timeout', 'refresh');
            die();
        }
        //setting the booking info into session
        $this->session->set_userdata('booking_info', $sent);
//        print_r($this->session->userdata('booking_info'));exit;
        $result['htltype'] = $sent['hotel_id'];
        $roomtypedata = $this->TA_Booking_Model->getcategory($sent['hotel_id'], 1);
        $result['roomtypedata'] = $roomtypedata;
        for ($i = 0; $i < count($roomtypedata); $i++) {
            $hotel_id = $roomtypedata[$i]['hotel_id'];
            $cat_id = $roomtypedata[$i]['cat_id'];
            //get the package info
            $result['package_info'][] = $this->getallactivepackages($hotel_id, $cat_id, $sent);
        }

//        print_r($result['package_info']);die;

        $this->load->view('TA_Booking/bookingpackages', $result);
        $this->load->view("footer");
    }

    private function getallactivepackages($hotel, $category, $sent) {
        if (empty($sent)) {
            redirect(base_url() . 'hotel/timeout', 'refresh');
            die();
        }
        $pack = explode('~', $sent['packagetype']);
        $packagetype = $pack[0];
        extract($sent);
        //$fromdate = $sent['from_date'];
        //$todate = $sent['to_date'];
        $today = date('Y-m-d');
	//print_r(strtotime($fromdate) <= strtotime($today));die;
        if (strtotime($fromdate) <= strtotime($today) && $packagetype == 'H') {
            return "check in should be greater than today";
        } else if (strtotime($fromdate) < strtotime($today) && $packagetype != 'H') {
            return "check in should be greater than or equal to today";
        }

        if (strtotime($todate) <= strtotime($fromdate)) {
            return "check out should be greater than checkin date";
        }

        $res = $this->TA_Booking_Model->gethotelpackages($hotel, $category, $sent);
//         print_r($res);die;
        if (is_array($res)) {
            $rates = $this->getratesonly($res);
            $validities = $this->getvalidities($res);
            $packageinfo = $this->getpackageinfo($res);
            $adultchildinfo = $this->getadultchildinfo($res);

            $result['rates'] = $rates;
            $result['validity'] = $validities;
            $result['packageinfo'] = $packageinfo;
            $result['adultchildinfo'] = $adultchildinfo;
            $calculation = '';
            if (is_array($adultchildinfo)) {
	        $calculation = $this->getcalculation($packageinfo, $adultchildinfo, $rates, $validities,$hotel,$category);

                $this->load->library('session');

                $packageid = array_column($packageinfo, 'packid');
                $this->session->set_userdata('packageid', $packageid);

                // harish
                $totaltaxes = number_format((array_sum(array_column($calculation, 'taxamt'))), 2);
                $this->session->set_userdata('preparetaxes', $totaltaxes);
                // harish

                //$amountpayable = array_column($calculation, 'amountpayable');
                $amountpayable = array_column($calculation, 'amountpayable');
                $this->session->set_userdata('package_after_tax_amt', $amountpayable);

                //$packageroomcharges = array_column($calculation, 'roomcharges');
                $packageroomcharges = array_column($calculation, 'totalcost');
                $this->session->set_userdata('packageroomcharge', $packageroomcharges);

                $fromdates = array_column($validities, 'fromdate');
                $this->session->set_userdata('perpackagefrmdate', $fromdates);

                $todates = array_column($validities, 'todate');
                $this->session->set_userdata('perpackagetodate', $todates);

                $packagediscountamt = array_column($calculation, 'discountamt');
                $this->session->set_userdata('packagediscamt', $packagediscountamt);

                // daytour30AUG2019
                $daytouradultsamount = number_format((array_sum(array_column($calculation, 'daytouradultamt'))), 2);
                $this->session->set_userdata('daytouradultsprice', $daytouradultsamount);

                $daytourchildsamount = number_format((array_sum(array_column($calculation, 'daytourchildamt'))), 2);
                $this->session->set_userdata('daytourchildsprice', $daytourchildsamount);

                $daytouradultstax = number_format((array_sum(array_column($calculation, 'daytouradulttaxamt'))), 2);
                $this->session->set_userdata('daytouradultstax', $daytouradultstax);

                $daytourchildstax = number_format((array_sum(array_column($calculation, 'daytourchildtaxamt'))), 2);
                $this->session->set_userdata('daytourchildstax', $daytourchildstax);
                // daytour30AUG2019


                $finalamtaftertax = number_format((array_sum(array_column($calculation, 'amountpayable'))), 2);
                $finalroomcharges = number_format((array_sum(array_column($calculation, 'roomcharges'))), 2);
                $finalroomcharges = str_replace(',', '', $finalroomcharges);
                $finalamtaftertax = str_replace(',', '', $finalamtaftertax);

                $this->session->set_userdata('finaltotalamt', $finalamtaftertax);
                $this->session->set_userdata('finalamt', $finalamtaftertax);
                $this->session->set_userdata('finalroomcharges', $finalroomcharges);
		$roomcommission = number_format((array_sum(array_column($calculation, 'room_commission'))), 2);
       		$roomcommission = str_replace(',', '', $roomcommission);

		$this->session->set_userdata('roomcommission', $roomcommission);

                $roomcharges = number_format((array_sum(array_column($calculation, 'totalcost'))), 2);
                $this->session->set_userdata('roomcharges', $roomcharges);

                $totaldiscountamt = number_format((array_sum(array_column($calculation, 'discountamt'))), 2);
                $this->session->set_userdata('totaldiscountamt', $totaldiscountamt);
            }
            $result['calculation'] = $calculation;
            return $result;
        } else {
            return $res;
        }
    }

    private function getratesonly($res) {
        $ratearray = array();
        foreach ($res as $key => $val) {
            $packid = $val['package_id'];
            $nightcount = $val['hdpr_no_nights'];
            $ratearray[$packid][$nightcount]['nightcount'] = $nightcount;
            $ratearray[$packid][$nightcount]['couple'] = $val['hdpr_couple_cost'];
            $ratearray[$packid][$nightcount]['adult'] = $val['hdpr_extra_adult'];
            $ratearray[$packid][$nightcount]['child'] = $val['hdpr_extra_child'];
        }
        //echo "<pre>"; print_r($ratearray); echo "</pre>"; exit;
        return $ratearray;
    }

    private function getvalidities($res) {
        $requested = $this->getRequested();
        if (empty($requested)) {
            $requested = $this->input->post();
            //echo "hi";
        }
        $arr = array();
        foreach ($res as $key => $val) {
            $fromdate = $val['package_valid_from'];
            $todate = $val['package_valid_to'];
            if (strtotime($requested['fromdate']) < strtotime($fromdate)) {
                $fromdate = $fromdate;
            } else {
                $fromdate = $requested['fromdate'];
            }

            if (strtotime($requested['todate']) > strtotime($todate)) {
                //$todate = $todate;
                $todate = date('d-m-Y', strtotime($todate . '+1 day'));
            } else {
                $todate = $requested['todate'];
            }

            $datediff = strtotime($todate) - strtotime($fromdate);
            $nights = floor($datediff / (60 * 60 * 24));
            $todate = date('Y-m-d', strtotime($todate));
            $packid = $val['package_id'];
            $arr[$packid]['fromdate'] = $fromdate;
            $arr[$packid]['todate'] = $todate;
            $arr[$packid]['nights'] = $nights;
        }
        return $arr;
    }

    private $sent = array();

    public function getRequested() {
        return $this->sent;
    }

    private function getpackageinfo($res) {
        
        $packageinfo = array();
        foreach ($res as $val) {
            $packid = $val['package_id'];
            $packageinfo[$packid]['packid'] = $packid;
            $packageinfo[$packid]['packname'] = $val['package_name'];
            $packageinfo[$packid]['hotel'] = $val['package_hotel'];
            #$packageinfo[$packid]['taxes'] = $val['package_taxes']; # MODIFIED BY siddu -- 06.08.18
            $packageinfo[$packid]['taxes'] = $val['hdpr_taxes']; # MODIFIED BY siddu -- 06.08.18
            $packageinfo[$packid]['ex_adult_taxes'] = $val['hdpr_extra_adult_tax']; # ADDED BY siddu --06.08.18
            $packageinfo[$packid]['ex_child_taxes'] = $val['hdpr_extra_child_tax']; # ADDED BY SIDDU --06.08.18
            $packageinfo[$packid]['packagetype'] = $val['package_type'];
            $packageinfo[$packid]['category_id'] = $val['category_id'];
            $packageinfo[$packid]['category_name'] = $val['category_name'];
            $packageinfo[$packid]['maxper_rate'] = $val['package_max_per_rate'];
            $packageinfo[$packid]['minrooms'] = $val['hotels_minrooms'];
            $packageinfo[$packid]['maxrooms'] = $val['hotels_maxrooms'];
            $packageinfo[$packid]['package_description'] = $val['package_description'];
            $packageinfo[$packid]['checkin'] = $val['package_checkin'];
            $packageinfo[$packid]['checkout'] = $val['package_checkout'];
            $packageinfo[$packid]['nights_pack'] = $val['hdpr_no_nights'];
        }
        return $packageinfo;
    }

    private function getadultchildinfo($res) {
        //echo "<pre>"; print_r($res); echo "</pre>"; exit;
        $sent = $this->getRequested();
        if (empty($sent)) {
            $sent = $this->input->post();
            //echo "hi";
        }
        $adultchild = array();
        foreach ($res as $key => $val) {
            $packid = $val['package_id'];
            $req_rooms = intval($sent['rooms']);

            $minrooms = intval($val['hotels_minrooms']);
            $maxrooms = intval($val['hotels_maxrooms']);

            if ($req_rooms > $maxrooms) {
                return "more than maximum rooms are being booked";
            }
            /* if ($req_rooms < $minrooms) {
                return 'less than minimum rooms are being booked';
            } */

            /* if ($req_rooms > 5) {
              return "Maximum 5 rooms can be alloted";
              } */

            $req_adults = intval($sent['adults']);
            $req_child = intval($sent['child']);

            $total_req_per = $req_rooms * intval($val['package_total_pax']);
            $total_req_pax = intval($req_adults) + intval($req_child);
            if ($total_req_per < $total_req_pax) {
                return "Maximum " . $total_req_per . " pax can be accomodated in " . $req_rooms . " rooms";
                //return "Maximum no of persons for " . $req_rooms . " rooms exceeded";
            }

            $maxadultspercatg = $val['package_max_adults'];
            $maxadults = $req_rooms * $maxadultspercatg;
            if ($req_adults > $maxadults) {
                //return "Maximum adults should be multiples of " . $maxadultspercatg;
                return "Maximum adults accomodation can be " . $maxadults;
            }

            if ($req_adults < (1 * $req_rooms)) {
                return "Minimum 1 adult per room required";
            }

            if (($req_adults) == ($val['package_max_per_rate'] * $req_rooms)) {
                $extra = 0;
            } else if (($req_adults) > ($val['package_max_per_rate'] * $req_rooms)) {
                $extra = intval(($req_adults) % ($val['package_max_per_rate'] * $req_rooms));
            } else if ($req_adults < ($val['package_max_per_rate'] * $req_rooms) + 1) {
                $extra = intval(($req_adults) / ($val['package_max_per_rate'] * $req_rooms));
            }

            $maxchildpercatg = $val['package_max_child'];
            $maxchild = $req_rooms * $maxchildpercatg;

            if ($req_child > $maxchild) {
                return $maxchild . " childs allowed ";
            }

            $onlycouples = $req_adults - $extra;

            $adultchild[$packid]['adults'] = $onlycouples;
            $adultchild[$packid]['child'] = $req_child;
            $adultchild[$packid]['extra'] = $extra;
            $adultchild[$packid]['rooms'] = $req_rooms;
            // $adultchild[$packid]['night'] = $sent['nights'];
        }
        return $adultchild;
    }

    private function getcalculation($packageinfo, $adultchildinfo, $rates, $validities,$hotel,$cat) {
        $sent = $this->getRequested();
        if (empty($sent)) {
            $sent = $this->input->post();
        }
        extract($sent);
        $calculation = array();
        foreach ($packageinfo as $key => $val) {
            $childinfo = $adultchildinfo[$key]['child'];
            $coupleinfo = $adultchildinfo[$key]['adults'];
            $extrainfo = $adultchildinfo[$key]['extra'];

            $fromdate = $validities[$key]['fromdate'];
            $todate = $validities[$key]['todate'];

            $datediff = strtotime($todate) - strtotime($fromdate);
            $nights = floor($datediff / (60 * 60 * 24));

            $ncnt_arr = array_column($rates[$key], 'nightcount');

            $maxnightdeclared = max($ncnt_arr);
            $var1 = intval($nights / $maxnightdeclared);
            $var2 = intval($nights % $maxnightdeclared);
            $couplerate = $rates[$key][$maxnightdeclared]['couple'] * $var1;
            if ($var2 > 0)
                $couplerate += $rates[$key][$var2]['couple'];


            $extraadult_rate = $rates[$key][$maxnightdeclared]['adult'] * $var1;
            if ($var2 > 0)
                $extraadult_rate += $rates[$key][$var2]['adult'];

            $extrachild_rate = $rates[$key][$maxnightdeclared]['child'] * $var1;
            if ($var2 > 0)
                $extrachild_rate += $rates[$key][$var2]['child'];


            $couplecost = $couplerate * $rooms;
            $extraadultcost = $extrainfo * $extraadult_rate;
            $extrachildcost = $childinfo * $extrachild_rate;

            $room_category = $packageinfo[$key]['category_id'];
            $packageid = $key;

            //$discountamt = $this->getDiscountAmt($couplecost, $packageid, $fromdate, $todate, $room_category);
            $discountamt = 0;

            #$taxes = $this->getTaxes($couplecost, $packageid, $fromdate, $todate, $room_category,$hotel,$cat);
                #$adult_tax =  $taxes[0]['hdpr_extra_adult_tax'];
                #$child_tax =  $taxes[0]['hdpr_extra_child_tax'];

                # added by siddu dated --17.08.18 --for storing taxes in session 

                #$this->session->set_userdata('hdpr_taxes',$taxes[0]['hdpr_taxes']);
                #$this->session->set_userdata('hdpr_extra_adult_tax',$taxes[0]['hdpr_extra_adult_tax']);
                #$this->session->set_userdata('hdpr_extra_child_tax',$taxes[0]['hdpr_extra_child_tax']);

	    # ADDED BY - siddu -- 06.008.18
            $couplecost1        = $couplecost - ($discountamt);
            #$taxes              = intval($taxes[0]['hdpr_taxes']);
            #$taxes             = intval($val['taxes']);
            #$fincouplecost_tax  = $couplecost1 *($taxes / 100);

            #$ex_adult_taxes     = intval($adult_tax);
            #$ex_adult_taxes    = intval($val['ex_adult_taxes']);
            #$finadultcost_tax   = $extraadultcost *($ex_adult_taxes / 100);

           # $ex_child_taxes     = intval($child_tax);
            #$ex_child_taxes    = intval($val['ex_child_taxes']);
           # $finchildcost_tax   = $extrachildcost *($ex_child_taxes / 100);
// GST

            // $couplecst =  ($couplecost1 / $nights);
          // if($hotel != 5){
            $total = $couplecost1 + $extraadultcost + $extrachildcost;
            $couplecst =  ($total / ($nights*$rooms));
            $gstslab = $this->TA_Booking_Model->getgstslabs($couplecst,$hotel);

            // print_r($gstslab);

           // foreach ($gstslab as $key => $value) {

               // $couplegst = $value['couple_gst'];
               // $adultgst = $value['extra_adult_gst'];
                //$childgst = $value['extra_child_gst'];  
		$couplegst = $gstslab[0]['couple_gst'];
                $adultgst = $gstslab[0]['extra_adult_gst'];
                $childgst = $gstslab[0]['extra_child_gst']; 
           // }

                $gstcouple = $couplecost1 * ($couplegst / 100);
                $gstextraadult = $extraadultcost * ($adultgst / 100);
                $gstextrachild = $extrachildcost * ($childgst / 100);
		
		if($hotel == 5 && $sent['fromdate'] == '2019-12-30' || $sent['fromdate'] == '2019-12-31'){
                $couplegst = 18;
                $adultgst = 18;
                $childgst = 18;

                $gstcouple = $couplecost1 * ($couplegst / 100);
                $gstextraadult = $extraadultcost * ($adultgst / 100);
                $gstextrachild = $extrachildcost * ($childgst / 100);
                }

                $this->session->set_userdata('hdpr_taxes', $couplegst);
                $this->session->set_userdata('hdpr_extra_adult_tax', $adultgst);
                $this->session->set_userdata('hdpr_extra_child_tax', $childgst);
           // }else{
             //   $gstcouple = 0;
               // $gstextraadult = 0;
               // $gstextrachild = 0;                 
           // }
// GST
            $roomcharges1 = intval($couplerate);

            // daytour30AUG2019
                if(isset($daytourid)){
                    $n = $this->TA_Booking_Model->hotels_daytour_ratesfinal($hotel_id, $cat, $nights, $fromdate, $daytourid);
                    $maincount = count($n);
                // echo $this->db->last_query();exit();
                if($maincount > 0){
                    foreach ($n as $key => $value) {
                       $daytouradult = $value['adult_price'];
                       $daytourchild = $value['child_price'];
                       $daytouradulttax = $value['adult_tax'];
                       $daytourchildtax = $value['child_tax'];
                       $daytourpackageinfo = $value['package_info'];
                       $daytourpackagetitle = $value['title'];

                       $this->load->library('session');
                       $this->session->set_userdata('packageinfo', $daytourpackageinfo);
                       $this->session->set_userdata('title', $daytourpackagetitle);
                    }
                }
                    $daytouradultamt = $daytouradult * ($coupleinfo + $extrainfo);
                    $daytourchildamt = $daytourchild * $childinfo;

                    $daytouradulttaxamt = ($daytouradultamt) * ($daytouradulttax / 100);
                    $daytourchildtaxamt = ($daytourchildamt) * ($daytourchildtax / 100);
                }else{
                    $daytouradultamt = 0;
                    $daytourchildamt = 0;
                    $daytouradulttaxamt = 0;
                    $daytourchildtaxamt = 0;
                }
           // $taxamt             = ($fincouplecost_tax) + ($finadultcost_tax) + ($finchildcost_tax) + ($daytouradulttaxamt) + ($daytourchildtaxamt);
	    $taxamt             = ($gstcouple) + ($gstextraadult) + ($gstextrachild);
            $roomcharges        = intval($couplecost) + intval($extraadultcost) + intval($extrachildcost) + intval($daytouradultamt) + intval($daytourchildamt);
            $finalamt           = intval($couplecost1) + intval($extraadultcost) + intval($extrachildcost) + intval($daytouradultamt) + intval($daytourchildamt);
            // daytour30AUG2019

            // $taxamt             = ($fincouplecost_tax) + ($finadultcost_tax) + ($finchildcost_tax);
            // $roomcharges        = intval($couplecost) + intval($extraadultcost) + intval($extrachildcost);
            // $finalamt           = intval($couplecost1) + intval($extraadultcost) + intval($extrachildcost);
      
	    # ADDED BY - siddu -- 06.008.18

            $calculation[$key]['packageni8'] = $ncnt_arr;
            $calculation[$key]['couplecost'] = $couplecost;
            $calculation[$key]['extraadultcost'] = $extraadultcost;
            $calculation[$key]['extrachildcost'] = $extrachildcost;
            $calculation[$key]['roomcharges'] = $roomcharges;
            $calculation[$key]['room_commission'] = $roomcharges1;
            $calculation[$key]['taxamt'] = $taxamt;
            $calculation[$key]['totalcost'] = $finalamt + $taxamt;
            #$calculation[$key]['totalcost'] = $finalcharges + $taxamt;
            $calculation[$key]['discountamt'] = ($discountamt);
            $calculation[$key]['amountpayable'] = ceil($finalamt + $taxamt);
            #$calculation[$key]['amountpayable'] = ceil($finalcharges+ $taxamt);
            // daytour30AUG2019
            $calculation[$key]['daytouradultamt'] = $daytouradultamt;
            $calculation[$key]['daytourchildamt'] = $daytourchildamt;
            $calculation[$key]['daytouradulttaxamt'] = $daytouradulttaxamt;
            $calculation[$key]['daytourchildtaxamt'] = $daytourchildtaxamt;
            // daytour30AUG2019
        }
        return $calculation;
    }

    private function getTaxes($couplefinalcost, $packageid, $checkin, $checkout, $room_category,$hotel,$cat){
        $this->load->model('TA_Booking_Model');
        return $this->TA_Booking_Model->getTaxes($couplefinalcost, $packageid, $checkin, $checkout, $room_category,$hotel,$cat);
    }

    private function getDiscountAmt($couplefinalcost, $packageid, $checkin, $checkout, $room_category) {
        $this->load->model('TA_Booking_Model');
        return $this->TA_Booking_Model->getDiscount($couplefinalcost, $packageid, $checkin, $checkout, $room_category);
    }

    // daytour30AUG2019
    public function hotelsaddons(){
        $header = $this->session->userdata('getheader');
        $this->load->view($header);
        $args = func_get_args();
        if (func_num_args() < 2 || func_num_args() > 2)
            redirect('booking/timeout', 'refresh');
        if ($args[0] == 0 || $args[1] == 0) {
            redirect('booking/timeout', 'refresh');
        }
        $hotel = $args[0];
        $category = $args[1];
        $userbookingdata = $this->session->userdata('booking_info');
        $datediff = strtotime($userbookingdata['todate']) - strtotime($userbookingdata['fromdate']);
        $nights = floor($datediff / (60 * 60 * 24));
        $daytourdata['daytouraddon'] = $this->TA_Booking_Model->hotels_daytour_rates($hotel, $category, $nights);
        $n = count($daytourdata['daytouraddon']);
        if($n <= 0 ){
            $url = base_url() . 'hotel/visitorinformation/'.$hotel.'/'.$category.'';
             echo "<script>window.location.href = '$url';</script>";exit;
        }
        $daytourdata['hotel'] = $hotel;    
        $daytourdata['category'] = $category;
        $this->load->view('TA_Booking/daytouraddon', $daytourdata);
        $this->load->view('footer');

        // echo '<pre>'; print_r($daytourdata);exit;
    }
    // daytour30AUG2019

    public function visitorinformation(){
        $header = $this->session->userdata('getheader');
            $this->load->view($header);
        /*if ($this->session->userdata('paymentpage')) {
            redirect(base_url() . 'hotel', 'refresh');
        }*/
        // daytour30AUG2019
        $id = $this->input->post('id');
        // daytour30AUG2019
        $args = func_get_args();
        if (func_num_args() < 2 || func_num_args() > 2)
            redirect('booking/timeout', 'refresh');
        if ($args[0] == 0 || $args[1] == 0) {
            redirect('booking/timeout', 'refresh');
        }
        //        print_r($this->session->userdata('booking_info'));exit;
        $this->session->set_userdata('visitorpage', '1');
        $hotel = $args[0];
        $category = $args[1];
        $userbookingdata = $this->session->userdata('booking_info');
        $userbookingdata['country'] = $this->TA_Booking_Model->getcountry();
        $userbookingdata['idproof'] = $this->TA_Booking_Model->getidproofs();
        $userbookingdata['hotel'] = $hotel;
        $this->sent = $this->session->userdata('booking_info');

        $rooms = $userbookingdata['rooms'];
        $hotelid = $userbookingdata['hotel_id'];
	    $checkindate = $userbookingdata['fromdate'];
        $checkoutdate = $userbookingdata['todate'];
        $datediff = strtotime($userbookingdata['todate']) - strtotime($userbookingdata['fromdate']);
        $nights = floor($datediff / (60 * 60 * 24));

        $sessiondata = $this->session->userdata('userdetails');
        $user_id = $sessiondata['ta_user_id'];
        $owner_id = $sessiondata['ta_owner_id'];
        // daytour30AUG2019
        if(isset($id)){
        $this->sent['daytourid'] = $id;
        $booking_count = $this->sent['adults'] + $this->sent['child'];
        $daytourcommission = $this->UserModel->calculateCommission($booking_count, $owner_id, $sc_special= 0);
        $this->session->set_userdata('daytourcommission', $daytourcommission);
        }else{
        // daytour30AUG2019
        $this->session->unset_userdata('daytourcommission');
        $this->session->unset_userdata('packageinfo');
        $this->session->unset_userdata('title');
        // daytour30AUG2019
        $this->sent;    
        }
        // daytour30AUG2019
        $userbookingdata['res'] = $this->getallactivepackages($hotel, $category, $this->sent);
        $this->session->set_userdata('hotel', $hotel);
        $this->session->set_userdata('category', $category);
//        print_r($res);die;
        //$res=$this->getallactivepackages()
//        print_r($userbookingdata);exit;

        $roomtypedata = $this->getcatg($hotel, $category, 1);
        $userbookingdata['roomname'] = $roomtypedata[0]['category_name'];
        $roomnames = $this->TA_Booking_Model->getroomscount($hotel);
        $userbookingdata['hotelname'] = $roomnames[0]['hotels_name'];

        $userbookingdata['travelagent_info'] = $this->UserModel->getTravelAgentInfo($user_id);
        // daytour30AUG2019
        $userbookingdata['daytourcommisssion'] = $daytourcommission;
        // daytour30AUG2019
        $userbookingdata['commission'] = $this->UserModel->hotelnewcommission($hotelid, $rooms, $nights,$checkindate,$checkoutdate);

        // Harish December4 Hotels Count
                    if ($sessiondata['ta_user_role_id'] == 7 || $sessiondata['ta_user_role_id'] == 2) {
                        $travelagent_htcnt = $sessiondata['ta_owner_id'];
                    }
                    if ($sessiondata['ta_user_role_id'] == 9) {
                        $travelagent_htcnt = $sessiondata['ta_user_id'];
                    }
            $userbookingdata['hotelroomscnt']= $this->TA_Booking_Model->gethotelscount($hotel, $category,$travelagent_htcnt);
            $packageid = array_unique(array_column($userbookingdata['res']['packageinfo'], 'packid'));
            $userbookingdata['complementarycost'] = $this->TA_Booking_Model->onenightcoupleamount($hotel, $category, $checkindate, $packageid[0]);
            $this->session->set_userdata('checkpackageid', $packageid[0]);
        // Harish December4 Hotels Count

//        $this->load->view('TA_Booking/visitorinformation', $userbookingdata);
        $this->load->view('TA_Booking/visitordetails', $userbookingdata);
        $this->load->view('footer');
    }

    private function getcatg($hotel, $category, $val = 0) {
        return $this->TA_Booking_Model->getcatg($hotel, $category, $val);
    }

    public function getstate($country) {
        $state = $this->TA_Booking_Model->getstates($country);
        $st = '';
        foreach ($state as $key => $val) {
            $st .= "<option value=" . $val['hotels_state_code'] . ">" . $val['hotels_state_name'] . "</option>";
        }
        echo $st;
    }


    public function payment() {
        if ($this->session->userdata('visitorpage')) {
        } else {
            redirect(base_url() . 'hotel/timeout', 'refresh');
            die();
        }
        $this->session->set_userdata('paymentpage', '1');
        $hotel = $this->session->userdata('hotel');
        $category = $this->session->userdata('category');
        $fromdate = $this->session->userdata('finalfromdate');
        $todate = $this->session->userdata('finaltodate');
        $constructorname = $this->router->class;
        $sent = $this->getRequested();
        if (empty($sent)) {
            $sent = $this->session->userdata('formfields');
        }
        if (empty($sent)) {
            $sent = $this->input->post();
        }
        extract($sent);
        $res = $this->TA_Booking_Model->roomavailability($fromdate, $todate, $hotel, $category);
        if (is_null($res)) {
            die("rooms not available");
            exit;
        }
        $findval = 0;
        if (in_array($findval, $res, true)) {
            die('rooms not available for given days');
            exit;
        }
        if (!$hotel || trim($hotel) == '') {
            redirect(base_url() . 'booking/timeout', 'refresh');
            die();
        } else {
            $data = $this->getRequested();
            if (empty($data)) {
                $data = $this->session->userdata('formfields');
            }

            $values = $this->input->post();
            $finaltotalamt = $this->session->userdata('finaltotalamt');
            $finaltotalamt = str_replace(",", "", $finaltotalamt);
            $totalprice = $finaltotalamt;

            $sessiondata = $this->session->userdata('userdetails');
            if ($sessiondata['ta_user_role_id'] == 7 || $sessiondata['ta_user_role_id'] == 2) {
                $travelagent_info = $this->UserModel->getTravelAgentInfo($sessiondata['ta_owner_id']);
            }
            if ($sessiondata['ta_user_role_id'] == 9) {
                $travelagent_info = $this->UserModel->getTravelAgentInfo($sessiondata['ta_user_id']);
            }
            $walletamt = $travelagent_info[0]->wallet_amount;
            $wallet = $this->input->post('wallet');
            $company = $this->input->post('company');
            //set whether pay through the wallet is checked or not in session
            $wallet1 = array(
                'wallet' => $wallet,
            );
            $this->session->set_userdata('paythroughwallet', $wallet1);
            $company1 = array(
                'company' => $company,
            );
            $this->session->set_userdata('Billtocompany', $company1);
            $pending_amount = 0;
            if ($company == 1) {
                $totalprice1 = $totalprice * 0.25;
                $totalprice1 = 0;
                //$pending_amount = $totalprice * 0.75;
                $pending_amount = $totalprice - $totalprice1;
                if ($wallet == 1) {
                    if ($totalprice1 >= $walletamt) {
                        $balancepayment = $totalprice1 - $walletamt;
                        $walletbalance = 0;
                    } else {
                        $walletbalance = $walletamt - $totalprice1;
                        $balancepayment = 0;
                    }
                    //  echo "wallet balance after deduction" . $walletbalance;
                    // echo "remaining balance payment after including " . $balancepayment;die;
                } else {
                    $balancepayment = $totalprice1;
                    $walletbalance = $walletamt;
                }
            } elseif ($wallet == 1) {
                if ($totalprice >= $walletamt) {
                    $balancepayment = $totalprice - $walletamt;
                    $walletbalance = 0;
                } else {
                    $walletbalance = $walletamt - $totalprice;
                    $balancepayment = 0;
                }
            } else {
                $balancepayment = $totalprice;
                $walletbalance = $walletamt;
            }
            if ($company == 1) {
                $pending_type = 1;
            } else {
                $pending_type = 0;
            }
            $values['pending_type'] = $pending_type;
            $values['pending_amount'] = $pending_amount;
            // harish
            $wallet_pay = $walletamt-$walletbalance;
            $values['user_resv_wallet_amount'] = $wallet_pay;
            // harish
            // Harish December4 Hotels Count
            if($complementary == 1){
            $packid = $this->session->userdata('checkpackageid', $packageid[0]);
            $complementarycost = $this->TA_Booking_Model->onenightcoupleamount($hotel, $category, $fromdate, $packid);
            $values['complementarycost'] = $complementarycost;
                if ($totalprice >= $complementarycost) {
                    $balancepayment = $totalprice - $complementarycost;
                     $walletbalance = $walletamt;
                    $pending_type = 0;
                }
            }else{
                $values['complementarycost'] = 0;
            }
            // Harish December4 Hotels Count
            if ($balancepayment <= 0) {
                if ($wallet == 1 && $company == 1) {
                    $mode = "WALLET AND BILL TO COMPANY";
                } elseif ($wallet == 1) {
                    $mode = "WALLET";
                } else {
                    $mode = "BILL TO COMPANY";
                }
                $orderno = $this->TA_Booking_Model->saveuserdata($values, $mode);
                if ($orderno != '0') {
                    $this->session->set_userdata('reservation_number', $orderno);
                }

                $bookinginfo = $this->TA_Booking_Model->getUserBookingInformation($orderno);
                $values['nights'] = $bookinginfo[0]['user_summary_no_of_nights'];
                $values['rooms'] = $bookinginfo[0]['user_summary_total_rooms'];
                $values['final_amt'] = $bookinginfo[0]['user_resv_final_amount'];

                $values['hotelid'] = $bookinginfo[0]['hotels_id'];
                $values['user_summary_registration_no'] = $bookinginfo[0]['user_summary_registration_no'];

                $values['ticket_id'] = $orderno;
                $values['wallet_bal'] = $walletbalance;
                $values['balancepayment'] = $balancepayment;

                //here we are the resetting the session data when paid by wallet is used
                $finaltotalamt = $this->session->userdata('finaltotalamt');

                $resp = $this->UserModel->paidbywalletHotel($values);
                if ($resp) {
                    $bookinginfo = $this->TA_Booking_Model->getUserBookingInformation($orderno);
                    $this->getmaildata($orderno, 'W');

                    $update['nights'] = $bookinginfo[0]['user_summary_no_of_nights'];
                    $update['rooms'] = $bookinginfo[0]['user_summary_total_rooms'];
                    $update['final_amt'] = $bookinginfo[0]['user_resv_final_amount'];

                    $update['hotelid'] = $bookinginfo[0]['hotels_id'];
                    $update['user_summary_registration_no'] = $bookinginfo[0]['user_summary_registration_no'];
                    $update['ticket_id'] = $orderno;

                    // harish 
                    $update['checkindate'] = $bookinginfo[0]['user_summary_chk_in'];
                    $update['checkoutdate'] = $bookinginfo[0]['user_summary_chk_out'];
                    $update['finaltotalamt'] = $this->session->userdata('finaltotalamt');
                    $update['final_amt'] = $this->session->userdata('finaltotalamt'); 
                    $update['hotel']= $bookinginfo[0]['hotels_name'];
                    $update['no_of_nights'] = $bookinginfo[0]['user_summary_no_of_nights'];
                    $update['total_rooms'] = $bookinginfo[0]['user_summary_total_rooms'];
                    $update['adult_count']= $bookinginfo[0]['user_summary_adult_count'];
                    $update['child_count']= $bookinginfo[0]['user_summary_child_count'];
                    $update['fromdate'] = $bookinginfo[0]['user_summary_chk_in'];
                    $update['todate'] = $bookinginfo[0]['user_summary_chk_out'];
                    $update['mobile']= $bookinginfo[0]['user_resv_user_mobile'];
                    $update['email']= $bookinginfo[0]['user_resv_user_email'];
                    $update['user_resv_first_name']= $bookinginfo[0]['user_resv_first_name'];
                    $update['user_resv_last_name']= $bookinginfo[0]['user_resv_last_name'];
                    $update['user_resv_registration_no']= $bookinginfo[0]['user_resv_registration_no'];
                    $update['beforetax']= $bookinginfo[0]['user_resv_reservation_beforetax'];
                    $update['aftertax']= $bookinginfo[0]['user_resv_reservation_aftertax'];
                    $update['totaltaxes']= $bookinginfo[0]['user_resv_tax_amount'];
                    $update['hotelcategory']= $bookinginfo[0]['user_summary_category_code'];
                    $update['useraddress']= $bookinginfo[0]['user_resv_address'];
                    $update['modeofpayment']= $bookinginfo[0]['user_resv_source'];
                    $update['salutation']= $bookinginfo[0]['user_resv_user_title'];
                    $update['createddate']= $bookinginfo[0]['user_resv_reservation_date'];
                    $update['lastname']= $bookinginfo[0]['user_resv_last_name'];
                    $update['city']= $bookinginfo[0]['user_resv_user_city'];
                    $update['state']= $bookinginfo[0]['hotels_state_name'];
                    $update['countryname']= $bookinginfo[0]['smt01_country_name'];
                    $update['discountamt']= $bookinginfo[0]['user_resv_discount_amount'];
                    $this->pushapi($update);
                    // harish

                    $msg = "Your booking is confirmed";

                    $bookinginfo = $this->TA_Booking_Model->getUserBookingInformation($orderno);
                    if ($bookinginfo[0]['user_resv_status'] == '3') {
                        $status = "3";
                    } else if ($bookinginfo[0]['user_resv_status'] == '2') {
                        $status = "2";
                    } else
                        $status = "1";

                    if ($bookinginfo[0]['user_resv_source'] == 'mobile') {
                        $dev = "mobile";
                    } else {
                        $dev = "web";
                    }

                    foreach ($bookinginfo as $key => $val) {

                        $packcode = $val['user_summary_pack_code'];
                        $arr[$orderno]['registration_no'] = $val['user_summary_registration_no'];
                        $arr[$orderno]['totalamount'] = $val['user_resv_final_amount'];
                        $arr[$orderno]['mobile'] = $val['user_resv_user_mobile'];
                        $arr[$orderno]['email'] = $val['user_resv_user_email'];
                        $arr[$orderno]['name'] = $val['user_resv_first_name'] . " " . $val['user_resv_last_name'];
                        $arr[$orderno]['hotel'] = $val['hotels_name'];
                        $arr[$orderno]['roomtype'] = $val['category_name'];
                        $arr[$orderno]['fromdate'] = $val['user_summary_chk_in'];
                        $arr[$orderno]['todate'] = $val['user_summary_chk_out'];
                    }

                    $res['packagenames'] = array_column($bookinginfo, 'package_name');
                    $res['packageamounts'] = array_column($bookinginfo, 'user_summary_total_amount');
                    $res['packagediscamounts'] = array_column($bookinginfo, 'user_summary_discount_amount');
                    $res['packagefinalamounts'] = array_column($bookinginfo, 'user_summary_final_amount');

                    $res['status'] = $status;
                    $res['device'] = $dev;
                    $res['arr'] = $arr;

                    $this->session->set_flashdata('response', "Booking Successful, ThankYou!");
                    redirect(base_url() . 'hotel/responsepage');
                } else {
                    $this->session->set_flashdata('response', "Booking failed! Please try again.");
                    redirect(base_url() . 'hotel/responsepage');
                }
            }
            $this->session->set_userdata('finalamt', $balancepayment);
            $finaltotalamt = $this->session->userdata('finalamt');
	    //here we are keeping the balancepayment(after including the wallet) in final amt
            $result = $this->TA_Booking_Model->saveuserdata($values);
            if ($result != '0') {
                $this->session->set_userdata('reservation_number', $result);
                $payment['paymenturl'] = $this->paymenturl;
                $payment['merchantid'] = $this->merchantid;
                $payment['securityid'] = $this->securityid;
                $payment['key'] = $this->password;
                $payment['na'] = $this->na;
                $payment['reservation'] = $result;
                $payment['username'] = $values['firstname'];
                $payment['mobile'] = $values['mobile'];
                $payment['email'] = $values['email'];
                $payment['wallet_bal'] = $walletbalance;
                if (!isset($sent['source'])) {
                    $payment['returnurl'] = base_url() . $constructorname . "/confirmation";
                } else {
                    $payment['returnurl'] = base_url() . $constructorname . "/mobileconfirmation";
                }

                $payment['res'] = $payment;

                $this->load->view('TA_Booking/payment', $payment);
            } else {
                if (isset($data['source'])) {
                    if ($devtype != "ios") {
                        ?>
                        <script>  function getMsg() {
                                var msg = message;
                                AndroidFunction.gotMsg('failed to save', 'failed');
                            }
                            getMsg();</script>
                        <?php
                    } else {
                        ?>
                        <script type="text/javascript">
                            function getMsg() {
                                var msg = "Problem persists. Please try again later.";
                                var status = "failed";
                                window.webkit.messageHandlers.gotmessage.postMessage(msg, status);
                            }
                            getMsg();
                        </script>

                        <?php
                    }
                } else {
                    echo "failed";
                    redirect(base_url() . 'hotel/timeout', 'refresh');
                    die();
                }
            }
        }
    }

    public function responsepage(){
           $header = $this->session->userdata('getheader');
            $this->load->view($header);
       // $this->load->view('TA_Booking/hotels_header');
            // daytour30AUG2019
            $this->session->unset_userdata('daytourcommission');
            $this->session->unset_userdata('packageinfo');
            $this->session->unset_userdata('title');
            // daytour30AUG2019
        $this->load->view('TA_Booking/booking_summary');
        $this->load->view('footer');
    }

    public function confirmation() {
        $sessiondata = $this->session->userdata('userdetails');
        $user_id = $sessiondata['ta_user_id'];
        //print_r($sessiondata['ta_user_id']);die;
        $orderno = $this->session->userdata('reservation_number');
        if ((!isset($orderno)) || ($orderno == '')) {
//            redirect('http://www.hotelsatramojifilmcity.com/price');
            redirect(base_url() . 'hotel/timeout', 'refresh');
            //redirect('');
            return;
        }

        $data = $_REQUEST;

        $checkstatus = $this->captureResponse($data);
        // print_r($checkstatus);die;

        if ($checkstatus['return_code'] == '0300') {
            $status = "2";
        } else {
            $status = "3";
        }

        $return_msg = $data['msg'];
        $pieces = explode('|', $return_msg);
        $errordesc = explode(',', $pieces[24]);
        $errorstatus = explode(',', $pieces[23]);
        $this->session->set_userdata('billdesc', $errordesc[0]);

	if ($return_msg != '') {
           $resp = $this->TA_Booking_Model->billdesk($pieces, $orderno, 'auto', $status, $errorstatus, $errordesc);
        }

        if ($status == "2") {

            $bookinginfo = $this->TA_Booking_Model->getUserBookingInformation1($orderno);
//            print_r($bookinginfo);
//            die;
            $update['nights'] = $bookinginfo[0]['user_summary_no_of_nights'];
            $update['rooms'] = $bookinginfo[0]['user_summary_total_rooms'];
	    $update['checkindate'] = $bookinginfo[0]['user_summary_chk_in'];
	    $update['checkoutdate'] = $bookinginfo[0]['user_summary_chk_out'];
            $update['final_amt'] = $bookinginfo[0]['user_resv_final_amount'];

            $update['hotelid'] = $bookinginfo[0]['hotels_id'];
            $update['user_summary_registration_no'] = $bookinginfo[0]['user_summary_registration_no'];
            $update['ticket_id'] = $orderno;
            // $update['amt'] = $this->session->userdata('finaltotalamt');
            // $update['wallet_bal'] = $walletbalance;

            $update['finaltotalamt'] = $this->session->userdata('finaltotalamt');
            $update['final_amt'] = $this->session->userdata('finaltotalamt');
            //here the txnamt  from bill desk response and (set that as paid by online) and (set finaltotalamt-txnamt = paid by wallet)
            // harish            
            $update['hotel']= $bookinginfo[0]['hotels_name'];
            $update['no_of_nights'] = $bookinginfo[0]['user_summary_no_of_nights'];
            $update['total_rooms'] = $bookinginfo[0]['user_summary_total_rooms'];
            $update['adult_count']= $bookinginfo[0]['user_summary_adult_count'];
            $update['child_count']= $bookinginfo[0]['user_summary_child_count'];
            $update['fromdate'] = $bookinginfo[0]['user_summary_chk_in'];
            $update['todate'] = $bookinginfo[0]['user_summary_chk_out'];
            $update['mobile']= $bookinginfo[0]['user_resv_user_mobile'];
            $update['email']= $bookinginfo[0]['user_resv_user_email'];
            $update['user_resv_first_name']= $bookinginfo[0]['user_resv_first_name'];
            $update['user_resv_last_name']= $bookinginfo[0]['user_resv_last_name'];
            $update['user_resv_registration_no']= $bookinginfo[0]['user_resv_registration_no'];
            $update['beforetax']= $bookinginfo[0]['user_resv_reservation_beforetax'];
            $update['aftertax']= $bookinginfo[0]['user_resv_reservation_aftertax'];
            $update['totaltaxes']= $bookinginfo[0]['user_resv_tax_amount'];
            $update['hotelcategory']= $bookinginfo[0]['user_summary_category_code'];
            $update['useraddress']= $bookinginfo[0]['user_resv_address'];
            $update['modeofpayment']= $bookinginfo[0]['user_resv_source'];
            $update['salutation']= $bookinginfo[0]['user_resv_user_title'];
            $update['createddate']= $bookinginfo[0]['user_resv_reservation_date'];
            $update['lastname']= $bookinginfo[0]['user_resv_last_name'];
            $update['city']= $bookinginfo[0]['user_resv_user_city'];
            $update['state']= $bookinginfo[0]['hotels_state_name'];
            $update['countryname']= $bookinginfo[0]['smt01_country_name'];
            $update['discountamt']= $bookinginfo[0]['user_resv_discount_amount'];
            // Harish December4 Hotels Count
            $update['complementaryamount'] =$bookinginfo[0]['user_resv_complementary_amount'];
            
                    if ($sessiondata['ta_user_role_id'] == 7 || $sessiondata['ta_user_role_id'] == 2) {
                        $travelagent_htcnt = $sessiondata['ta_owner_id'];
                    }
                    if ($sessiondata['ta_user_role_id'] == 9) {
                        $travelagent_htcnt = $sessiondata['ta_user_id'];
                    }
                    if($update['complementaryamount'] > 0){
                    $this->TA_Booking_Model->updatehotelscount($update['hotelid'],$update['hotelcategory'],$travelagent_htcnt);
                    }
                    $hotesdata = array(
                        'hotelid' => $update['hotelid'],
                        'rooms_count' => $update['rooms'],
                        'category' => $update['hotelcategory'],
                        'agentid' => $travelagent_htcnt,
                    );
                    $this->TA_Booking_Model->hotelwisecount($hotesdata);
            // Harish December4 Hotels Count
            $this->pushapi($update);
            // harish

            $billdeskinfo = $this->TA_Booking_Model->getbilldeskInfo($orderno);
            $update['balancepayment'] = $billdeskinfo->txnamount;
            $walletpay = $finaltotalamt - $billdeskinfo->txnamount; //use this if required
            $update['wallet_bal'] = $billdeskinfo->additionalinfo5; //remaining wallet balance
            // daytour30AUG2019
            $daytourcommission = $this->session->userdata('daytourcommission');
            if(isset($daytourcommission)){
                $update['daytourcommission'] = $daytourcommission; 
            }else{
                $daytourcommission = 0;
                $update['daytourcommission'] = $daytourcommission;
            }
            // daytour30AUG2019
            $this->UserModel->updateHotelBookingData($update);
        }else{
            $this->session->set_flashdata('response', "Booking failed! Please try again.");
                    redirect(base_url() . 'hotel/responsepage');
        }

	if (($resp == '1' || $resp == 1) && $status == "2") {
            $orderno = $this->session->userdata('reservation_number');
            $this->getmaildata($orderno);
            //$bookinginfo = $this->Booking_model->getUserBookingInformation($orderno);
            //$upd = $this->Booking_model->upstatus($status, $orderno);
        }

        if ($pieces[22] == 'mobile'){
            ?>
            <script type="text/javascript">
                function getMsg() {
                    var msg = "<?php echo base_url(); ?>hotel/paymentstatus?orderno1=<?php echo $orderno; ?>";
                            //var msg = "<?php echo base_url(); ?>hotel/paymentstatus?orderno1=SI201703@1490854546";
                            AndroidFunction.gotMsg(msg);
                        }
                        getMsg();
            </script>
            <?php
        } else {
            $this->session->set_flashdata('response', "Booking Successful, ThankYou!");
            // daytour30AUG2019
            $this->session->unset_userdata('daytourcommission');
            $this->session->unset_userdata('packageinfo');
            $this->session->unset_userdata('title');
            // daytour30AUG2019
            redirect(base_url() . 'hotel/responsepage');
        }
    }


     // harish

   public function pushapi($update){
    $orderno = $update['user_resv_registration_no'];
    $jsonmessage= '{
                    "Authentication":{
                    "UserName":"RamojiHotels@Reznext.com",
                    "Password":"Reznext@RamojiHotels"
                    },
                    "HotelCode":'.$update["hotelid"].',
                    "BookingMode":"Book",
                    "ChannelBookingUniqId":"'.$update['user_resv_registration_no'].'",
                    "ChannelBookingCancellationUniqID":"",
                    "profileinfo":{
                    "HotelCode":'.$update["hotelid"].',
                    "ProfileUniqueId":"",
                    "ProfileContext":"",
                    "ProfileType":"",
                    "ProfileName":"'.$update['user_resv_first_name']. $update['user_resv_last_name'].'",
                    "AddressLine":"'.$update['useraddress'].'",
                    "CityName":"'.$update['city'].'",
                    "StateCode":"",
                    "Country":"'.$update['countryname'].'",
                    "PostalCode":"",
                    "PhoneNumber":"'.$update['mobile'].'",
                    "Email":"'.$update['email'].'",
                    "ContactName":"'.$update['user_resv_first_name']. $update['user_resv_last_name'].'",
                    "ContactPhone":"'.$update['mobile'].'",
                    "MarketCode":"CNR",
                    "BusinessSource":"CNR",
                    "SalesOfficeCode":"",
                    "salesOfficeDescp":"",
                    "BillAddressType":"",
                    "BillAddressLine":"",
                    "BillCityName":"",
                    "BillPostalCode":"",
                    "BillStateCode":"",
                    "BillCountry":"",
                    "BillPhoneNumber":"",
                    "BillEmail":""
                    },
                    "ReservationStays":[
                    {
                    "HotelCode":'.$update["hotelid"].',
                    "BookingStayUniqId":"",
                    "ChannelBookingUniqID":"'.$update["user_resv_registration_no"].'",
                    "ChannelChainID":"",
                    "SourceSystem":"GreenPark",
                    "HotelName":"'.$update["hotel"].'",
                    "BookingMode":"Book",
                    "BookingStatus":"Confirm",
                    "RoomTypeCode":'.$update["hotelcategory"].',
                    "RatePlanCode":"B2C00001",
                    "MealPlanCode":"CP",
                    "RatePlanCategory":"BAR",
                    "RatePlanDescription":"Continental Plan",
                    "CurrencyCode":"INR",
                    "NumberofUnit":'.$update['total_rooms'].',
                    "GstCountIsPerRoom":true,
                    "AdultCount":'.$update["adult_count"].',
                    "ChildCount":'.$update["child_count"].',
                    "ArrivalDate":"'.$update["fromdate"].'",
                    "DepartureDate":"'.$update["todate"].'",
                    "DayDuration":"'.$update["no_of_nights"].'",
                    "TOTAmountAfterTax":"'.$update['aftertax'].'",
                    "TOTAmountBeforeTax":"'.$update['beforetax'].'",
                    "TaxCurrency":"INR",
                    "TaxAmount":"'.$update["totaltaxes"].'",
                    "GuaranteeType":"none",
                    "GuarantyPayAmount":0,
                    "GuarantyPayCurrency":"INR",
                    "GpaymentCardNumber":"",
                    "GpaymentCardType":"",
                    "GpaymentCardCode":"",
                    "GpaymentCardExpiredate":"",
                    "GpaymentCardseriescode":"",
                    "GpaymentCardholderName":"",
                    "GuaranteeDescription":"",
                    "BookingCreateDateTime":"'.$update['createddate'].'",
                    "Remarks":"",
                    "CancellationPolicy":"",
                    "CancelRefundAmount":0,
                    "Smoking":false,
                    "PaymentMode":"CRD",
                    "CommissionAmount":0,
                    "BillingAddress":"",
                    "GuestInfo":[
                    {
                    "Nameprefix":"'.$update["salutation"].'",
                    "GivenName":"'.$update['user_resv_first_name'].'",
                    "MiddleName":"'.$update['user_resv_last_name'].'",
                    "Surname":"",
                    "ArrivalDate":"'.$update['fromdate'].'",
                    "AddressLine":"'.$update['useraddress'].'",
                    "CityName":"'.$update['city'].'",
                    "PostalCode":"",
                    "StateCode":"'.$update['state'].'",
                    "Country":"'.$update['countryname'].'",
                    "EmailID":"'.$update['email'].'",
                    "PhoneNumber": "'.$update['mobile'].'",
                    "Nationality":"IN "
                    }
                    ],
                    "RateInfo":[
                    {
                    "EffectiveDate":"'.$update['fromdate'].'",
                    "EffectiveStartDate":"'.$update['fromdate'].'",
                    "EffectiveEndDate":"'.$update['todate'].'",
                    "BaseAmountAfterTax":'.$update['aftertax'].',
                    "BaseAmountBeforeTax":'.$update['beforetax'].',
                    "Currency":"INR"
                    }
                    ],
                    "Services":[
                    ]
                    }
                    ]
               }';

      $bookeddata =  $jsonmessage;
      $ch = curl_init('http://rednewreznextstandardreservation.azurewebsites.net/api/Reservation/PushReservation');                                                                    
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
      curl_setopt($ch, CURLOPT_POSTFIELDS, $bookeddata);                                                                  
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
          'Content-Type: application/json',                                                                                
          'Content-Length: ' . strlen($bookeddata))                                                                       
      );                                                                                                                          
      $result = curl_exec($ch);
      $this->TA_Booking_Model->reservationapisave($result,$bookeddata,$orderno);

    
    }

    // harish


    public function paymentstatus() {
        $orderno = $this->session->userdata('reservation_number');
        if ($orderno == '') {
            $orderno = $_GET['orderno1'];
        }

        if ((!isset($orderno)) || ($orderno == '')) {
//            redirect('http://www.ramojifilmcity.com');
            redirect(base_url() . 'hotel/timeout');
            return;
        }
        //$this->load->model('Booking_model');
        //$reservation = $this->Booking_model->getRoomOrder($orderno);
        // view for payment confirmation

        $bookinginfo = $this->TA_Booking_Model->getUserBookingInformation($orderno);
//         print_r($bookinginfo[0]['user_resv_status']);die;
        if ($bookinginfo[0]['user_resv_status'] == '3') {
            $status = "3";
        } else if ($bookinginfo[0]['user_resv_status'] == '2') {
            $status = "2";
        } else
            $status = "1";

        if ($bookinginfo[0]['user_resv_source'] == 'mobile') {
            $dev = "mobile";
        } else {
            $dev = "web";
        }

        foreach ($bookinginfo as $key => $val) {

            $packcode = $val['user_summary_pack_code'];

            //$arr[$orderno]['packagecode']= $val['user_summary_pack_code'];
            $arr[$orderno]['registration_no'] = $val['user_summary_registration_no'];
            //$arr[$orderno]['totalamount'] = $val['user_resv_total_amount'];
            $arr[$orderno]['totalamount'] = $val['user_resv_final_amount'];
            $arr[$orderno]['mobile'] = $val['user_resv_user_mobile'];
            $arr[$orderno]['email'] = $val['user_resv_user_email'];
            $arr[$orderno]['name'] = $val['user_resv_first_name'] . " " . $val['user_resv_last_name'];
            $arr[$orderno]['hotel'] = $val['hotels_name'];
            $arr[$orderno]['roomtype'] = $val['category_name'];
            $arr[$orderno]['fromdate'] = $val['user_summary_chk_in'];
            $arr[$orderno]['todate'] = $val['user_summary_chk_out'];
        }

        $res['packagenames'] = array_column($bookinginfo, 'package_name');
        $res['packageamounts'] = array_column($bookinginfo, 'user_summary_total_amount');
        $res['packagediscamounts'] = array_column($bookinginfo, 'user_summary_discount_amount');
        $res['packagefinalamounts'] = array_column($bookinginfo, 'user_summary_final_amount');


        $res['status'] = $status;

        $res['device'] = $dev;
	//print_r($arr);die;
        $res['arr'] = $arr;
        $this->load->view('TA_Booking/paymentstatus', $res);
        $this->dest_session();
    }

    private function dest_session() {
        $this->session->unset_userdata('reservation_number');
        $this->session->unset_userdata('booking_info');
        $this->session->unset_userdata('hotel');
        $this->session->unset_userdata('category');
        $this->session->unset_userdata('packageid');
        $this->session->unset_userdata('package_after_tax_amt');
        $this->session->unset_userdata('packageroomcharge');
        $this->session->unset_userdata('perpackagefrmdate');
        $this->session->unset_userdata('perpackagetodate');
        $this->session->unset_userdata('packagediscamt');
        $this->session->unset_userdata('finaltotalamt');
        $this->session->unset_userdata('roomcharges');
        $this->session->unset_userdata('totaldiscountamt');
        // daytour30AUG2019
        $this->session->unset_userdata('daytourcommission');
        $this->session->unset_userdata('packageinfo');
        $this->session->unset_userdata('title');
        // daytour30AUG2019
    }

    private function captureResponse($data) {
        $return_msg = $data['msg'];
        $pieces = explode('|', $return_msg);
        $transaction_id = $pieces[1];
        $return_code = $pieces[14];

        $return_checksum = array_pop($pieces);
        $check_msg = join("|", $pieces);

        $new_checksum = $this->encryptData($check_msg);

        # IF CHECKSUMS DON'T MATCH
        if ($new_checksum != $return_checksum) {
            return array(
                'transaction_id' => $transaction_id,
                'success' => false,
                'error_msg' => $pieces[24],
                "return_msg" => $return_msg,
                "return_crc" => $return_checksum,
                "return_code" => $return_code);
        }

        # IF STATUS CODE IS NOT 0300
        if ($pieces[14] != "0300") {
            return array(
                'transaction_id' => $transaction_id,
                'success' => false,
                'error_msg' => $pieces[24],
                "return_msg" => $return_msg,
                "return_crc" => $return_checksum,
                "return_code" => $return_code);
        }

        return array(
            'transaction_id' => $transaction_id,
            'success' => true,
            'error_msg' => $pieces[24],
            "return_msg" => $return_msg,
            "return_crc" => $return_checksum,
            "return_code" => $return_code);
    }

    private function encryptData($data) {
        $encrypt_with_password = $this->pg_sign_message($data);
        return crc32($encrypt_with_password);
    }

    private function pg_sign_message($postData) {
        return $postData . "|" . $this->password;
    }

    private function getmaildata($orderno, $payType = '') {
        $sessiondata = $this->session->userdata('userdetails');
        $orderno = $this->session->userdata('reservation_number');
        if ($payType != '') {
            $bookinginfo = $this->TA_Booking_Model->getUserBookingInformation($orderno); // For Wallet Payments
        } else {
            $bookinginfo = $this->TA_Booking_Model->getUserBookingInformation1($orderno); // For Other Payments
        }
       // echo '<pre>'; print_r($bookinginfo);

       // die;
        $hotelcode = $bookinginfo[0]['user_summary_hotel_id'];
        $categorycode = $bookinginfo[0]['user_summary_category_code'];

        $roomtypedata = $this->getcatg($hotelcode, $categorycode, 1);
        $room_or_bed = $roomtypedata[0]['category_label'];
        $haschild = $roomtypedata[0]['category_haschild'];
        if ($haschild != '1') {
            $children = 'no';
        } else
            $children = 'yes';

        $status = $bookinginfo[0]['user_resv_status'];
        $nights = $bookinginfo[0]['user_summary_no_of_nights'];
        $rooms  = $bookinginfo[0]['user_summary_total_rooms'];
        $adults = $bookinginfo[0]['user_summary_adult_count'];
        $child  = $bookinginfo[0]['user_summary_child_count'];
        $hotelid = $bookinginfo[0]['hotels_id'];

        $bankreferenceno = $bookinginfo[0]['bankreferenceno'];
        $txnreferenceno = $bookinginfo[0]['txnreferenceno'];
        $bookingthrough = $bookinginfo[0]['user_resv_source'];

        $mobile = $bookinginfo[0]['user_resv_user_mobile'];

        $reservationdate = $bookinginfo[0]['user_resv_reservation_date'];

        $reservation_no = array_unique(array_column($bookinginfo, 'user_resv_registration_no'));
        $reservation_no = $reservation_no[0];

        $firstname = array_unique(array_column($bookinginfo, 'user_resv_first_name'));
        $firstname = $firstname[0];

        $lastname = array_unique(array_column($bookinginfo, 'user_resv_last_name'));
        $lastname = $lastname[0];

        $title = array_unique(array_column($bookinginfo, 'user_resv_user_title'));
        $title = $title[0];

        $hotel = array_unique(array_column($bookinginfo, 'hotels_name'));
        $hotel = $hotel[0];

        $roomtype = array_unique(array_column($bookinginfo, 'category_name'));
        $roomtype = $roomtype[0];

        $checkin = min(array_column($bookinginfo, 'user_summary_chk_in'));
        $checkout = max(array_column($bookinginfo, 'user_summary_chk_out'));

	    $pending_type = $bookinginfo[0]['pending_type'];
        $pending_amount = $bookinginfo[0]['pending_amount'];

	//$checkout = max(array_column($validity, 'todate'));
        //$totalamount = array_unique(array_column($bookinginfo, 'user_resv_total_amount'));
        $totalamount = array_unique(array_column($bookinginfo, 'user_resv_final_amount'));
        $totalamount = $totalamount[0];
        // harish
        $wallet_amount = array_unique(array_column($bookinginfo, 'user_resv_wallet_amount'));
        $walletamount = $wallet_amount[0];

        $compamount = array_unique(array_column($bookinginfo, 'user_resv_complementary_amount'));
        $compamount = $compamount[0];

        $totalamount = $totalamount + $walletamount + $compamount;
        // harish

        $packdesc = array_unique(array_column($bookinginfo, 'package_description'));
        $packdesc = $packdesc[0];

        //$packagename = array_unique(array_column($bookinginfo, 'package_name'));
        $packagename = array_unique(array_column($bookinginfo, 'package_name'));
	    $packagename = $packagename[0];
        $hotelimg = array_unique(array_column($bookinginfo, 'hotels_mail_images'));

        // daytour30AUG2019
        $taxamount = array_unique(array_column($bookinginfo, 'user_resv_tax_amount'));
        $taxamount = $taxamount[0];

        $address = array_unique(array_column($bookinginfo, 'user_resv_address'));
        $address = $address[0];

        $daytouradultsamt = array_unique(array_column($bookinginfo, 'user_resv_daytouradults_amount'));
        $daytouradultamt = $daytouradultsamt[0];

        $daytourchildsamt = array_unique(array_column($bookinginfo, 'user_resv_daytourchilds_amount'));
        $daytourchildamt = $daytourchildsamt[0];

        $daytouradultstax = array_unique(array_column($bookinginfo, 'user_resv_daytouradultstax_amount'));
        $daytouradulttax = $daytouradultstax[0];

        $daytourchildstax = array_unique(array_column($bookinginfo, 'user_resv_daytourchildstax_amount'));
        $daytourchildtax = $daytourchildstax[0];

        $daytourat = $daytouradultamt + $daytouradulttax;
        $daytourct = $daytourchildamt + $daytourchildtax;
        // daytour30AUG2019

        $subject = "Reservation successful at " . $hotel . ', ' . $roomtype;
        $email = array_unique(array_column($bookinginfo, 'user_resv_user_email'));
        $email = $email[0];

        if ($status == 3) {
            $to = array('dhlrfc.mc@gmail.com', 'otsramojifilmcity@gmail.com');
            $subject = "Unsuccessful reservation at " . $hotel . " due to " . $bookinginfo[0]['user_resv_errordescription'];
        } else if ($status == 2) {
            $email = array_unique(array_column($bookinginfo, 'user_resv_user_email'));
            $email = $email[0];

            $to = array($email);
            $subject = "Successful reservation at " . $hotel;
        } else {
            $subject = '';
        }

 //        $body = '';
 //        // $body .= '<link rel=stylesheet href=' . base_url() . 'assets/booking/css/bootstrap.min.css />';
 //        $body .= "<html>";
 //        $body .= "<head>";
 //        $body .= '<meta charset="utf-8">';
 //        $body .= '<meta name="viewport" content="width=device-width, initial-scale=1">';
 //        $body .= '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">';
 //        $body .= '<style>.vals{ font-weight:bold;text-align:left;}</style>';
 //        $body .= "</head>";
	// //$body .= "<h2 class=text-center>Unsuccessful reservation at hotel " . $hotel . " due to " . $bookinginfo[0]['user_resv_errordescription'] . "</h2>";
 //        $body .= "<h2 class=text-center>" . $subject . "</h2>";
 //        $body .= '<div class=container><table class="table table-bordered">';
 //        if ($status != 3)
 //            $body .= '<tr><td colspan=4><img width=100% src=' . base_url() . $hotelimg[0] . '></td></tr>';
 //        $body .= '<tr><td colspan=1>Name</td>
 //                      <td colspan=3 class=vals>' . ucwords($firstname . " " . $lastname) . '</td>
 //                        </tr>
 //                       <tr>
	// 		    <td>Email</td>
 //                            <td class=vals>' . $email . '</td>
 //                            <td>Mobile</td>
 //                            <td class=vals>' . $mobile . '</td>
 //                        </tr>
 //                        <tr>
 //                            <td>Reservation Number</td>
 //                            <td class=vals>' . $reservation_no . '</td>
 //                            <td>Hotel Name</td>
 //                            <td class=vals>' . $hotel . '</td>
 //                        </tr>
 //                        <tr>
 //                            <td>Date & Time</td>
 //                            <td class=vals>' . date('d-m-Y H:i', strtotime($reservationdate)) . '</td>
 //                            <td>Room Type</td>
 //                            <td class=vals>' . $roomtype . '</td>
 //                        </tr>
 //                        <tr>
 //                            <td>Check In</td>
 //                            <td class=vals>' . date('d-m-Y', strtotime($checkin)) . ' 12:00 PM</td>
 //                            <td>No of ' . $room_or_bed . '</td>
 //                            <td class=vals>' . $rooms . '</td>
 //                        </tr>
 //                        <tr>
 //                            <td>Check out</td>
 //                            <td class=vals>' . date('d-m-Y', strtotime($checkout)) . ' 10:00 AM</td>
 //                            <td>No of Nights</td>
 //                            <td class=vals>' . $nights . '</td>
 //                        </tr>
 //                        <tr>
 //                            <td></td>
 //                            <td></td>
 //                            <td>No of Adults</td>
 //                            <td class=vals>' . $adults . '</td>
 //                        </tr>
 //                        <tr>
 //                            <td></td>
 //                            <td></td>
 //                            <td>No of Child</td>
 //                            <td class=vals>' . $child . '</td>
 //                        </tr>
 //                        <tr>
 //                            <td colspan=1>Amount </td>';
	// 		if ($pending_amount > 0)
 //                           $body .= '<td colspan=3 class=vals>' . $totalamount . ' (Bill to Company)</td>';
	// 		else
 //                           $body .= '<td colspan=3 class=vals>' . $totalamount . '</td>';
 //                        $body .= '</tr>';
	// /*if ($pending_amount > 0) {
 //           $body .= '<tr>';
 //           $body .= '<td colspan=1>Pending Amount </td>';
 //           if ($pending_type == 1){
 //              $body .= '<td colspan = 3 class = vals>' . $pending_amount . ' (Bill to Company) </td>';
 //           }
 //        $body .= '</tr>';
 //        } */

 //        foreach ($packagename as $key => $val) {
 //            $body .= '<tr>
 //                              <td colspan=1>Applied package</td>
 //                              <td colspan=3 class=vals>' . $val . '</td>
 //                           </tr>';
 //        }
 //        $body .= '<tr>
 //                            <td colspan=1>Booked Through</td>
 //                           <td colspan=3 class=vals>' . strtoupper($bookingthrough) . '</td>
 //                        </tr>';
 //        $body .= "<tr><td>Txn no</td><td class=vals>$txnreferenceno</td><td>Bank Ref.No</td><td class=vals>$bankreferenceno</td></tr>";
 //        //$body .= '<tr><td colspan=4 style="word-wrap:break:word;white-space: pre-line;">' . $packdesc . '</td></tr>';

 //        if ($status != 3) {

 //            $body .= '<tr>';
 //            $body .= "<td colspan=4><div style=\"margin:0px auto; padding:0px; font-size:10px;\">" . htmlspecialchars_decode($packdesc) . "</td></div>";
 //            $body .= '</tr>';
 //            $body .= '<tr>';
 //            $body .= "<td colspan=4><hr/><p>*Caution:</p>";

 //            $body .= "<ul style=\"margin:0px auto;font-size:12px;\">";
 //            $body .= "<li>In View Of Security Reasons, You Are Requested To Carry An Original Photo Identity Card (Driving License / Passport/Voter Id Card / Pan Card)</li>";
 //            $body .= "<li>Please use your best judment in deciding whether to fulfill or cancel this order.</li>";
 //            $body .= "<li>If this customer is using a stolen credit card, then you will lose money of this order.</li>";
 //            $body .= "<li>All disputes would be subject to RangaReddy jurisdiction.</li>";

 //            $contact_det = "<li>Kindly contact either of the following persons for any further assistance.</li>";


 //            // $to = array('hkumar516@gmail.com');
 //            // $cc = array('hkumar516@gmail.com');
 //            $to[] = $email; 
 //            $cc = array('dhlrfc.mc@gmail.com', 'ota@dolphinhotels.com', 'travelagents@ramojifilmcity.com');
	//         $cc[] = $sessiondata['ta_email_id'];
 //            //$cc = array('raghuk0@gmail.com');
 //            $body .= $contact_det;
 //            $body .= '</td></tr>';
 //        }
      
 //        $body .= "</table></div>";
 //        $body .= "</html>";
$body = '';
$body .= "<html lang='en'>";
$body .= "<head>";
$body .= "<title>Hotels</title>";
$body .= "<meta charset='utf-8'>";
$body .= "<meta name='viewport' content='width=device-width, initial-scale=1'>";
$body .= "<link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css'>";
$body .= "<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js'></script>";
$body .= "<script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js'></script>";
$body .= "<style>tr{border-style: hidden;}</style>";
$body .= "</head>";
$body .= "<body>";
$body .= "<div class='container'>";

if ($status != 3) {
            if ($hotelid == "1") {
                $contact_det .= "<li>Lobby Manger Hotel Sitara : 9392173174 / 08415-246666</li>";

                $to = array('fomsitara@dolphinhotels.com', 'otsramojifilmcity@gmail.com', 'holidays@dolphinhotels.com', 'revenue@dolphinhotels.com',
                    'centralreservations@dolphinhotels.com', 'accounts@dolphinhotels.com', 'cmaccounts@dolphinhotels.com');
            }
            if ($hotelid == "2") {
                $contact_det .= "<li>Lobby Manger Hotel Tara : 93921 73132 / 08415-246444</li>";

                $to = array('fomtara@dolphinhotels.com', 'otsramojifilmcity@gmail.com', 'revenue@dolphinhotels.com',
                    'centralreservations@dolphinhotels.com', 'accountstara@dolphinhotels.com', 'hollywood@dolphinhotels.com');
            }
            if ($hotelid == "3") {
                $contact_det .= "<li>Reception in Shantiniketan : 93921 73113 / 08415-246444</li>";

                $to = array('shantiniketan@dolphinhotels.com', 'otsramojifilmcity@gmail.com', 'revenue@dolphinhotels.com', 'fomtara@dolphinhotels.com',
                    'centralreservations@dolphinhotels.com', 'accounts@dolphinhotels.com', 'hollywood@dolphinhotels.com');
            }
            if ($hotelid == "4") {
                $contact_det .= "<li>Reception Vasundara Villa : 93921 73132 / 08415-246444</li>";

                $to = array('cmaccounts@dolphinhotels.com', 'otsramojifilmcity@gmail.com', 'revenue@dolphinhotels.com', 'suresh@eenadu.net',
                    'centralreservations@dolphinhotels.com', 'hollywood@dolphinhotels.com', 'shantiniketan@dolphinhotels.com');
            }
            if ($hotelid == "5") {
                //$contact_det .= "<li>Reception in Hotel Sahara : 93473 64903 / 08415-246444</li>";
                $contact_det .= "<li>Reception in Hotel Sahara : 93473 64903 / 93927 65080</li>";

                //$to = array('fomtara@dolphinhotels.com', 'otsramojifilmcity@gmail.com', 'revenue@dolphinhotels.com','cmaccounts@dolphinhotels.com',
                //   'centralreservations@dolphinhotels.com', 'accountstara@dolphinhotels.com', 'hollywood@dolphinhotels.com');
                $to = array('fosahara@dolphinhotels.com', 'managersahara@dolphinhotels.com', 'otsramojifilmcity@gmail.com', 'revenue@dolphinhotels.com',
                    'centralreservations@dolphinhotels.com', 'accounts@dolphinhotels.com', 'cmaccounts@dolphinhotels.com', 'incharge.digitalmktg@ramojifilmcity.com');
            }
            if ($hotelid == "7") {
                $contact_det .= "<li>Reception in Greens : 93473 64903 / 93927 65080</li>";

                $to = array('fosahara@dolphinhotels.com', 'managersahara@dolphinhotels.com', 'ukguda@dolphinhotels.com', 'otsramojifilmcity@gmail.com', 'revenue@dolphinhotels.com',
                    'centralreservations@dolphinhotels.com', 'accounts@dolphinhotels.com', 'cmaccounts@dolphinhotels.com', 'incharge.digitalmktg@ramojifilmcity.com');
            }
$body .= '<tr><td colspan=4><img width=100% src=' . base_url() . $hotelimg[0] . '></td></tr>';
$body .= "<p style='background-color:rgb(73, 183, 225);color: white'><b>Reservation Details</b></p>";
$body .= "<div class='table-responsive'>";
$body .= "<table class='table'>";
$body .= "<thead>";
$body .= "<tr>";
$body .= "<td><b>Booking Date :</b>". date('d-m-Y H:i', strtotime($reservationdate)) ."</td>";
$body .= "<td><b>Arival:</b>". date('d-m-Y', strtotime($checkin)) ."</td>";
$body .= "<td><b>Depature:</b>". date('d-m-Y', strtotime($checkout)) ."</td>";
$body .= "</tr>";
$body .= "</thead>";
$body .= "<tbody>";
$body .= "<tr>";
$body .= "<td><b>No of Rooms:</b>". $rooms ."</td>";
$body .= "<td><b>Room of guest :</b>". $roomtype ."</td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Guest Name:</b>". ucwords($firstname . " " . $lastname) ."</td>";
$body .= "<td><b>Guest Address:</b>". $address ."</td>";
$body .= "<td><b>Contact No:</b>". $mobile ."</td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Email Id:</b>". $email ."</td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Total Price:</b>INR ". $totalamount ."</td>";
$body .= "<td><b>GST:</b> INR ". $taxamount ."</td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Reservation Number :</b>". $reservation_no ."</td>";
$body .= "<td><b>Transaction Id:</b>". $txnreferenceno ."</td>";
$body .= "</tr>";
$body .= "</tbody>";
$body .= "</table>";
$body .= "<p style='background-color:rgb(73, 183, 225);color: white'><b>". $packagename ."</b></p>";
$body .= "<table class='table'>";
$body .= "<thead>";
$body .= "<tr>";
$body .= "<td><b>Guest Name :</b>". ucwords($firstname . " " . $lastname) ."</td>";
$body .= "</tr>";
$body .= "</thead>";
$body .= "<tbody>";
$body .= "<tr>";
$body .= "<td><b>Stay:</b> ". $roomtype ." - (". $nights ." Night Expirence)</td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Arrival:</b>". date('d-m-Y', strtotime($checkin)) ."</td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Departure:</b>". date('d-m-Y', strtotime($checkout)) ."</td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>No. of Nights:</b> ". $nights ."</td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Adults:</b> ". $adults ."</td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Children:</b> ". $child ."</td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Price:</b> INR ". $totalamount ."</td>";
$body .= "</tr>";
$body .= "</tbody>";
$body .= "</table>";

$body .= "<p style='background-color:rgb(73, 183, 225);color: white'><b>Tarrif Inclusions</b></p>";
$body .= "<table class='table'>";
$body .= "<thead>";
$body .= "<tr>";
$body .= "<td>".$packdesc."</td>";
$body .= "</tr>";
$body .= "</thead>";
$body .= "</table>";


$daytourtitle = $this->session->userdata('title');
if($daytourtitle !=''){
$body .= "<p style='background-color:rgb(73, 183, 225);color: white'><b>Day Tour Services</b></p>";
$body .= "<p><b>Ramoji Film City Studio Tour ". $daytourtitle ." for Adults = ".$adults." / Adult price = ".$daytourat." / Children = ".$child." / Children price = ".$daytourct."</b></p>";
}
$body .= "<p style='background-color:rgb(73, 183, 225);color: white'><b>Payment Details</b></p>";
$body .= "<table class='table'>";
$body .= "<thead>";
$body .= "<tr>";
$body .= "<td><b>Booking status: </b>". $bookinginfo[0]['user_resv_errordescription'] ."</td>";
$body .= "</tr>";
$body .= "</thead>";
$body .= "<tbody>";
$body .= "<tr>";
$body .= "<td><b>GST:</b> INR ". $taxamount ."</td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Total booking Cost:</b>INR ". $totalamount ."</td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Amount Paid:</b> INR ". $totalamount ."</td>";
$body .= "</tr>";
$body .= "</tbody>";
$body .= "</table>";
if($daytourtitle !=''){
$body .= "<p style='background-color:rgb(73, 183, 225);color: white'><b>Tarrif Inclusions</b></p>";
$body .= "<table class='table'>";
$body .= "<thead>";
$body .= "<tr>";
$body .= "<td><b>The Bed Tariff: </b></td>";
$body .= "</tr>";
$body .= "</thead>";
$body .= "<tbody>";
$body .= "<tr>";
$body .= "<td><ul><li>Complementary Buffet breakfast.</li><li>Ramoji Film City Studio Tour.</li><li>Rs 1000 Special rebate on Food Per Room Night.</li></ul></td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Hotel Timings:</b><ul><li>Check-in: 12:00 Noon Check-out: 10:00 AM</li></ul></td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Tour Timings:</b> <ul><li>Studio Tour visiting Hours : 9:30 AM to 5:30 PM</li></ul></td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>GST:</b> <ul><li>Above Rs.2500 to 7499 - 18% GST on Room basis</li></ul></td>";
$body .= "</tr>";
$body .= "<tr>";
$body .= "<td><b>Offerings at Extra Cost:</b> <ul><li>Ramoji film City studio Tour, [Add-on]</li><li>Lunch Dinner</li></ul></td>";
$body .= "</tr>";
$body .= "</tbody>";
$body .= "</table>";
$daytourinclusions = $this->session->userdata('packageinfo');
if($daytourinclusions !=''){
$body .= "<table class='table'>";
$body .= "<thead>";
$body .= "<tr>";
$body .= "<td><b>DayTour Inclusions</b></td>";
$body .= "</tr>";
$body .= "</thead>";
$body .= "<tbody>";
$body .= "<tr>";
$body .= "<td>". $daytourinclusions ."</td>";
$body .= "</tr>";
$body .= "</tbody>";
$body .= "</table>";
}
}
$body .= "<p style='background-color:rgb(73, 183, 225);color: white'><b>Terms and Conditions</b></p>";
$body .= "<table class='table'>";
$body .= "<tbody>";
$body .= "<tr>";
$body .= "<td><ul>
          <li>By entering the Park guests confirm that they have read, understood & agreed to all the terms & conditions, terms of use, rules and regulations, and policies associated with Ramoji Film City across online bookings, Park, & voluntarily assume any associated risks.
          </li>
          <li>All room occupants must display a valid Indian Government approved photo ID cards at the time of Check In, failing which may result in rejection of accommodation.
          </li>
          <li>All hotel bookings are subject to availability.
          </li>
          <li>All package rates are valid as per the dates mentioned only.
          </li>
          <li>Standard Check In time is 12: 00 hrs and Check Out is 10: 00 hrs.
          </li>
          <li>A Any Early Check In or Late Check Out may attract an additional half day charge plus taxes for a maximum of 4 hours and thereafter a full day rate (rate of the day) will be applicable.
          </li>
          <li>An extra Matress , if not included in the package, will be charged at an additional rate plus applicable taxes and will always be on subject to availability.
          </li>
          <li>The reservation does not guarantee the room allocation on the basis of the Guest's preference, such as king bed, twin bed, smoking room, etc. However we will always try to fulfil Guests' entire requirement.
          </li>
          <li>Prefer to bring your Monsoon wear.
          </li>
          <li>The hotel will be charging an additional amount as an advance token against the payment for availing other services/facilities in the hotel.
          </li>
          <li>Due to any inclement weather condition at any of the destinations, we are not liable for any kind of refund.
          </li>
          <li>As per the Hotel policy , Tara hotel is only having a Maximum Number of 02 audits ,1 Children (12 years below) are permitted to share a Room .
          </li>
          <li>Postponement of booking will be treated as cancellation and new booking will have to be made be subject to availability.
          </li>
          <li>Rates are subject to change without prior notice.
          </li>
          <li>A surcharge may be applicable during festival, peak and blackout dates.
          </li>
          <li>Please note there will be penalty of Rs. 3000 plus taxes & service charges in case guest is found smoking in a non-smoking room.
          </li>
          <li>In case of cash payment exceeding Rs. 24999, a PAN Card copy is mandatory from the Guest.
          </li>
          <li>Pets are not allowed.
          </li>
        </ul>";
$body .= "</td>";
$body .= "</tr>";
$body .= "</tbody>";
$body .= "</table>";
$body .= "<p style='background-color:rgb(73, 183, 225);color: white'><b>Cancellation Policy</b></p>";
$body .= "<table class='table'>";
$body .= "<tbody>";
$body .= "<tr>";
$body .= "<td><ul>
          <li>No Refund / Postponements would be entertained during Special packages like Summer Carnival, Dussehra, and Diwali & New Year Celebrations Packages etc. For other bookings on Holiday Package, if informed in writing before 21 days - Full Refund, Before 15 Days - 75% Refund, Before 7 Days - 50% Refund, No Refund thereafter. One postponement permitted if informed 15 days prior, for up to 90 days. Package rates are subject to change on management’s discretion, without prior notice, unless confirmed with advance amount.
          </li>
          <li>For continuation of stay subject to the availability of room(s), the usual per night Hotel tariff will be applicable as for a fresh reservation. For example, one night stay extended for one more night will not be considered as two night’s package. The hotel does not guarantee to fulfill the choice of room(s) mentioned in terms of type of Bed(s) and position of the room(s).There shall not be any compensations or refunds made in such cases.
          </li>
          <li>Right of Admissions reserved.
          </li>
          <li>House Rules and other Terms and Conditions of Dolphin Hotels Private Ltd. Apply.
          </li>
        </ul>
      </td>";
$body .= "</tr>";
            $to[] = $email;
            // $to[] = 'hkumar516@gmail.com';
            $cc = array('subhendu.jha@ramojifilmcity.com', 'dhlrfc.mc@gmail.com', 'ota@dolphinhotels.com', 'travelagents@ramojifilmcity.com');
            // $cc = array('hkumar516@gmail.com');

            $body .= $contact_det;
            $body .= '</td></tr>';
$body .= "</tbody>";
$body .= "</table>";
$body .= "</div>";
}
$body .= "</div>";
$body .= "</body>";
$body .= "</html>";

        $this->send_mail($to, $subject, $body, $cc);
    }

    public function timeout() {
        $result['device'] = "web";
        $this->load->view('TA_Booking/timeout', $result);
//        $this->session->sess_destroy();
        $this->dest_session();
        //redirect('http://www.hotelsatramojifilmcity.com/price', 'refresh');
        // echo "session timeout";
    }

    public function send_mail($to, $subject, $body, $cc) {
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->SMTPDebug = false;
        $mail->SMTPAuth = true;
        $mail->Host = "czismtp.logix.in";
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->Username = "ota@dolphinhotels.com";
        $mail->Password = "oTa@49%29";
        $mail->SetFrom("ota@dolphinhotels.com", "Dolphin Hotels Pvt Ltd");
        $mail->AddReplyTo("ota@dolphinhotels.com", "Dolphin Hotels Pvt Ltd");

        $mail->Subject = $subject;
        $mail->AltBody = "To view the message, Please use HTML";
        $mail->isHTML(true);

        foreach($to as $key=>$val) {
              $mail->AddAddress($val,'');
        }

        foreach($cc as $key=>$val) {
              $mail->AddCC($val,'');
        }

        $mail->MsgHTML($body);

        //Send mail 
        if ($mail->send()) {
            return 1;
        } else {
            return 0;
        }
    }

    public function reservations() {
       // print_r($_REQUEST);
        //die;
        $this->logcheck();
        $data['title'] = "Hotels Ramoji Film City";
        $data['paymenttype'] = $this->paymenttype();
        $today = date('Y-m-d');
        if (!empty($this->input->post())){
            $dat = $this->input->post();
            // print_r($dat);die;
            $info = $this->TA_Booking_Model->getReservations($dat);
            $data['info'] = $info;
            $data['payment'] = $dat['payment'];
            $data['hotel'] = $dat['hotel'];
            $data['frmdate'] = $dat['frmdate'];
            $data['todate'] = $dat['todate'];
            $data['type'] = $dat['type'];
            $data['device'] = $dat['device'];
        } else {
            $data['frmdate'] = $today;
            $data['todate'] = $today;
         //   $data['status'] = 'PAYMENT_MADE';
            $data['device'] = 'web';
            $data['payment'] = '1';
            $data['hotel'] = '1';
            $data['type'] = '2';
            $data['device'] ='0';
            $info = $this->TA_Booking_Model->getReservations($data);
            $data['info'] = $info;
        }
        $header = $this->session->userdata('getheader');
        $this->load->view($header);
        $this->load->view('TA_Booking/reservation', $data);
        $this->load->view('footer');
    }

    public function logcheck() {
        $filename = "adminlogs-" . date('Y-m-d') . ".txt";
        $this->writeLog($filename, $_SERVER, 'SERVER');
        $this->writeLog($filename, $_REQUEST, 'REQUEST');
        $this->writeLog($filename, $_POST, 'POST');
        $this->writeLog($filename, $_GET, 'GET');
        $this->writeLog($filename, $_SESSION, 'SESSION');

        if (!$this->session->userdata('isLoggedIn')) {
            redirect('admin/index');
        } else {
            return true;
        }
    }

    private function writeLog($filename, $method, $methodtype) {
        $f = fopen('travelagentlogs/' . $filename, 'a') or die("could not open file");
        $data = "RFC HOTELS ===> " . date('d-m-Y H:i:s') . " === Method Type ===> " . $methodtype . " === data ===> " . serialize($method) . "\n\n";
        fwrite($f, $data);
        fclose($f);
    }

    private function paymenttype() {
        $payments = $this->TA_Booking_Model->getpayments();
        return $payments;
    }


/*    public function getHotelsBookingByAgent() {
        //print_r($_REQUEST);die;
        $sessiondata = $this->session->userdata('userdetails');
        if (isset($sessiondata['ta_user_id']) && !empty($sessiondata['ta_user_id'])) {
            $header = $this->session->userdata('getheader');
            $this->load->view($header);
            if (!empty($this->input->post())) {
                $data = $this->input->post();
                $info = $this->TA_Booking_Model->getReservations($data);
                $result['info'] = $info;
                $result['payment'] = $data['payment'];
                $result['hotel'] = $data['hotel'];
                $result['fromdate'] = $data['frmdate'];
                $result['todate'] = $data['todate'];
            }
            $this->load->view('TA_Booking/reservationslist', $result);
            $this->load->view('footer');
        } else {
            redirect(base_url() . 'home/logout');
        }
    } */
}
?>
