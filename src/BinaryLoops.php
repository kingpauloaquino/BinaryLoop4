<?php

namespace king052188\BinaryLoops;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

use Carbon\Carbon;

use BLHelper;

class BinaryLoops
{
  private static $config_app;
  private static $config_services;

  public function __construct() {
    $this::$config_app = Config::get('app');
    $this::$config_services = Config::get('services');
  }

  // test function
  public function TestServices($showAll = false) {
    if($showAll) {
      return $this::$config_services;
    }

    if($this->Check_Point()) {
      return array(
        'BinaryLoops' => $this::$config_services["BinaryLoops"]
      );
    }

    return array(
      "Code" => $this::$err_code,
      "Message" => $this::$err_message
    );
  }

  // functions
  public function Encode($users, $request, $placement_id, $position_id) {
    $code = BLHelper::check_activation_code($request["code"]);
    if( $code == null ) {
      return ["Status" => 400, "Message" => "Invalid Activation Code", "Insert_Uid" => 0, "Member_Uid" => null];
    }

    $username = BLHelper::check_member_info($request["username"]);
    if( COUNT($username) > 0 ) {
      return ["Status" => 401, "Message" => "Username already exists.", "Insert_Uid" => 0, "Member_Uid" => null];
    }
    $multiple_account = $this->validate_multiple_accounts($request["email"], $request["mobile"]);
    if( $multiple_account["Status"] > 200 ) {
      return $multiple_account;
    }
    $placement = BLHelper::check_member_info($placement_id);
    if( COUNT($placement) == 0 ) {
      return ["Status" => 404, "Message" => "Pleacement did not found.", "Insert_Uid" => 0, "Member_Uid" => null];
    }
    if( (int)$position_id == 0 ) {
      return ["Status" => 405, "Message" => "Invalid position.", "Insert_Uid" => 0, "Member_Uid" => null];
    }
    $position = BLHelper::check_position_of_placement($placement_id, $position_id);
    if($position["Status"] > 0) {
      $p = $position_id == 21 ? 'left.' : 'right.';
      return ["Status" => 406, "Message" => "[" . $placement[0]->username . "] already has downline on his/her " . $p, "Insert_Uid" => 0, "Member_Uid" => null];
    }
    $cross_lining = BLHelper::check_is_crossline($users["member_uid"], $placement_id);
    if($cross_lining) {
      return ["Status" => 407, "Message" => "Cross-lining is not allowed.", "Insert_Uid" => 0, "Member_Uid" => null];
    }

    $dt = Carbon::now();
    $new_member_uid = BLHelper::generate_unique_id(null);
    $hex_code = sprintf('%06X', mt_rand(0, 0xFFFFFF));
    $encrypted_hexcode = bcrypt($hex_code);
    $passwords = $hex_code; //["Password"=> $hex_code, "Encrypted" => $encrypted_hexcode];
    $user_token = md5(sprintf('%06X', mt_rand(0, 0xFFFFFF)));

    $member_info = array(
      "user_token" => $user_token,
      "member_uid" => $new_member_uid,
      "username" => $request["username"] != "" ? $request["username"] : null,
      "password" => $encrypted_hexcode,
      "first_name" => $request["first_name"] != "" ? $request["first_name"] : null,
      "last_name" => $request["last_name"] != "" ? $request["last_name"] : null,
      "email" => $request["email"] != "" ? $request["email"] : null,
      "mobile" => $request["mobile"] != "" ? $request["mobile"] : null,
      "type" => $code->type, //1 Affliate  by Sponsor, 2 Encoded by Sponsor, 3 Commission Deduction Account, 4 Free Slot
      "status" => 2, //0 Deactivated Account, 1 Pending Account, 2 Activated Account
      "connected_to" => $users["id"],
      "activation_id" => $code->Id,
      'updated_at' => $dt,
      'created_at' => $dt
    );
    $result = BLHelper::add_member($member_info);
    if($result > 0) {
      $msg = "Welcome " . $request["first_name"] . ", thank you for registering in our system. Your Password is: {$hex_code}";
      BLHelper::sms_template($request["mobile"], $msg);
      // update code to status used
      BLHelper::check_activation_code($request["code"], true);
      $transaction_number = BLHelper::generate_reference();
      $genealogy = array(
        "transaction" => $transaction_number,
        "sponsor_id" => $users["member_uid"],
        "placement_id" => $placement_id,
        "member_uid" => $new_member_uid,
        "activation_code" => $code->code,
        "position_" => $position_id,
        "status_" => 2,
        'updated_at' => $dt,
        'created_at' => $dt
      );
      $result = BLHelper::add_member_genealogy($genealogy);
      if($result > 0) {
        BLHelper::lookup_genealogy($new_member_uid, $code->amount);
        return [
          "Status" => 200,
          "Message" => "Success.",
          "Insert_Uid" => $result,
          "Type" => $code->type,
          "Member_Uid" => $new_member_uid,
          "Password" => $passwords
        ];
      }
      return ["Status" => 500, "Message" => "Something went wrong. Error#: 002", "Insert_Uid" => $result, "Member_Uid" => $new_member_uid, "Password" => $passwords];
    }
    return ["Status" => 500, "Message" => "Something went wrong. Error#: 001", "Insert_Uid" => 0, "Member_Uid" => null, "Password" => null];
  }

  public function Encode_Via_UserUrl($sponsor_uid, $request) {
    $multiple_account = $this->validate_multiple_accounts($request["email"], $request["mobile"]);
    if( $multiple_account["Status"] > 200 ) {
      return $multiple_account;
    }

    $dt = Carbon::now();
    $new_member_uid = BLHelper::generate_unique_id(null);
    $hex_code = sprintf('%06X', mt_rand(0, 0xFFFFFF));
    $encrypted_hexcode = bcrypt($hex_code);
    $passwords = $hex_code; //["Password"=> $hex_code, "Encrypted" => $encrypted_hexcode];
    $user_token = md5(sprintf('%06X', mt_rand(0, 0xFFFFFF)));

    $member_info = array(
      "user_token" => $user_token,
      "member_uid" => $new_member_uid,
      "username" => $new_member_uid,
      "password" => $encrypted_hexcode,
      "first_name" => $request["first_name"] != "" ? $request["first_name"] : null,
      "last_name" => $request["last_name"] != "" ? $request["last_name"] : null,
      "email" => $request["email"] != "" ? $request["email"] : null,
      "mobile" => $request["mobile"] != "" ? $request["mobile"] : null,
      "type" => 1, //1 Affliate  by Sponsor, 2 Encoded by Sponsor, 3 Commission Deduction Account, 4 Free Slot
      "status" => 1, //0 Deactivated Account, 1 Pending Account, 2 Activated Account
      "connected_to" => (int)$sponsor_uid,
      "activation_id" => 0,
      'updated_at' => $dt,
      'created_at' => $dt
    );
    $result = BLHelper::add_member($member_info);
    if($result > 0) {
      $msg = "Welcome " . $request["first_name"] . ", thank you for registering in our system. Your Password is: {$hex_code}";
      BLHelper::sms_template($request["mobile"], $msg);
      return [
        "Status" => 200,
        "Message" => "Success.",
        "Member_Uid" => $new_member_uid,
        "Password" => $passwords
      ];
    }
    return ["Status" => 500, "Message" => "Something went wrong. Error#: 001", "Member_Uid" => null, "Password" => null];
  }

  public function Encode_Affliliates($users, $request, $placement_id, $position_id, $affliliate_uid) {
    $code = BLHelper::check_activation_code($request["code"]);
    if( $code == null ) {
      return ["Status" => 400, "Message" => "Invalid Activation Code", "Insert_Uid" => 0, "Member_Uid" => null];
    }

    $username = BLHelper::check_member_info($request["username"]);
    if( COUNT($username) > 0 ) {
      return ["Status" => 401, "Message" => "Username already exists.", "Insert_Uid" => 0, "Member_Uid" => null];
    }
    $placement = BLHelper::check_member_info($placement_id);
    if( COUNT($placement) == 0 ) {
      return ["Status" => 404, "Message" => "Pleacement did not found.", "Insert_Uid" => 0, "Member_Uid" => null];
    }
    if( (int)$position_id == 0 ) {
      return ["Status" => 405, "Message" => "Invalid position.", "Insert_Uid" => 0, "Member_Uid" => null];
    }
    $position = BLHelper::check_position_of_placement($placement_id, $position_id);
    if($position["Status"] > 0) {
      $p = $position_id == 21 ? 'left.' : 'right.';
      return ["Status" => 406, "Message" => "[" . $placement[0]->username . "] already has downline on his/her " . $p, "Insert_Uid" => 0, "Member_Uid" => null];
    }
    $cross_lining = BLHelper::check_is_crossline($users["member_uid"], $placement_id);
    if($cross_lining) {
      return ["Status" => 407, "Message" => "Cross-lining is not allowed.", "Insert_Uid" => 0, "Member_Uid" => null];
    }

    $dt = Carbon::now();
    $member_uid = $affliliate_uid;

    $member_info = array(
      "username" => $request["username"] != "" ? $request["username"] : null,
      "type" => $code->type, //1 Affliate  by Sponsor, 2 Encoded by Sponsor, 3 Commission Deduction Account, 4 Free Slot
      "status" => 2, //0 Deactivated Account, 1 Pending Account, 2 Activated Account
      "activation_id" => $code->Id,
      'updated_at' => $dt
    );

    $result = BLHelper::update_users($member_uid, $member_info);
    if($result > 0) {
      // update code to status used
      BLHelper::check_activation_code($request["code"], true);
      $transaction_number = BLHelper::generate_reference();
      $genealogy = array(
        "transaction" => $transaction_number,
        "sponsor_id" => $users["member_uid"],
        "placement_id" => $placement_id,
        "member_uid" => $member_uid,
        "activation_code" => $code->code,
        "position_" => $position_id,
        "status_" => 2,
        'updated_at' => $dt,
        'created_at' => $dt
      );
      $result = BLHelper::add_member_genealogy($genealogy);
      if($result > 0) {
        BLHelper::lookup_genealogy($member_uid, $code->amount);
        return [
          "Status" => 200,
          "Message" => "Success.",
          "Insert_Uid" => $result,
          "Type" => $code->type,
          "Member_Uid" => $new_member_uid,
          "Password" => $passwords
        ];
      }
      return ["Status" => 500, "Message" => "Something went wrong. Error#: 002", "Insert_Uid" => $result, "Member_Uid" => $member_uid];
    }
    return ["Status" => 500, "Message" => "Something went wrong. Error#: 001", "Insert_Uid" => 0, "Member_Uid" => null];
  }

  public function validate_multiple_accounts($email, $mobile) {
    $multiple_account = BLHelper::check_member_multiple_account($email, false);
    if( COUNT($multiple_account) > 0 ) {
      if($multiple_account["total_used"] > 6) {
        return ["Status" => 402, "Message" => "This email [". $email ."] has reached 7 accounts.", "Insert_Uid" => 0];
      }
      if($multiple_account["mobile"] != $mobile) {
        return ["Status" => 402, "Message" => "The mobile number should be same as the PRIMARY Account.", "Insert_Uid" => 0];
      }
    }

    $multiple_account = BLHelper::check_member_multiple_account($mobile, true);
    if( COUNT($multiple_account) > 0 ) {
      if($multiple_account["total_used"] > 6) {
        return ["Status" => 402, "Message" => "This mobile# [". $mobile ."] has reached 7 accounts.", "Insert_Uid" => 0];
      }
      if($multiple_account["email"] != $email) {
        return ["Status" => 402, "Message" => "The email address should be same as the PRIMARY Account.", "Insert_Uid" => 0];
      }
    }

    return ["Status" => 200, "Message" => "Success.", "Insert_Uid" => 0];
  }

  public function Placement_Validate($request) {
    $affliliate = IsSet($request["c"]) ? $request["c"] : null;

    $result = BLHelper::check_position_of_placement($request["a"], $request["b"], $affliliate);
    return $result;
  }

  public function Member_Pairing($member_uid) {
    $result = BLHelper::get_member_pairing_daily($member_uid);
    return $result;
  }

  public function Member_Structure_Details($member_uid) {
    $result = BLHelper::get_member_structure_details($member_uid);
    return $result;
  }

  public function Populate_Genealogy($username) {
    $result = BLHelper::get_genealogy_structure($username);
    return $result;
  }

  public function Populate_Leveling($username) {
    $result = BLHelper::get_leveling_summary($username, false);
    return $result;
  }

  public function Populate_Indirect($member_uid) {
    $json = BLHelper::get_reverse_indirect($member_uid);
    return $json;
  }

  public function Generate_Activation_Code(Request $request) {
    $json = BLHelper::get_activation_code(
      $request->qty,
      $request->type,
      $request->by,
      $request->for
    );
    return $json;
  }

  // public function Populate_Leveling($username, $position) {
  //   $result = BLHelper::get_leveling_structure($username, (int)$position);
  //   return $result;
  // }

  public function Populate_Multiple_Accounts($member_uid, $mobile, $limit = 7) {
    $result = BLHelper::get_multiple_accounts($member_uid, $mobile, $limit);
    return $result;
  }

  // classes

  public function getConfigApp($key = null) {
    if($key==null) {
      return $this::$config_app;
    }
    return $this::$config_app[$key];
  }

  public function getConfigServices() {
    return $this::$config_services;
  }

  public function Check_Point() {
    if(!IsSet($this::$config_services["BinaryLoops"])) {
      $this::$err_code = 301;
      $this::$err_message = "Please check your config/services.php";
      return false;
    }

    if(!IsSet($this::$config_services["BinaryLoops"]["host"])) {
      $this::$err_code = 302;
      $this::$err_message = "Please check your [HOST] in config/services.php";
      return false;
    }

    if(!IsSet($this::$config_services["BinaryLoops"]["email"])) {
      $this::$err_code = 303;
      $this::$err_message = "Please check your [EMAIL] in config/services.php";
      return false;
    }

    if(!IsSet($this::$config_services["BinaryLoops"]["license"])) {
      $this::$err_code = 304;
      $this::$err_message = "Please check your [LICENSE] in config/services.php";
      return false;
    }
    return true;
  }

  public function Curl($url = null, $data = []) {

    if($url == null) {
      return ["Status" => 401];
    }

    if(COUNT($data) == 0) {
      return ["Status" => 402];
    }

    // Array to Json
    $toJSON = json_encode($data);

    // Added JSON Header
    $headers= array('Accept: application/json','Content-Type: application/json');

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $toJSON);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $result;
  }


}
