<?php

namespace king052188\BinaryLoops;

use Illuminate\Support\Facades\Config;

use DB;
use App\User;
use Carbon\Carbon;

class BLBot
{

  public function init($request) {
    $members = DB::select("
    SELECT member_uid, username, mobile FROM users WHERE type != 0 AND type <= 5;
    ");

    $data = [];
    for($i = 0; $i < COUNT($members); $i++) {
      $m = BLHelper::get_member_structure_details(
        $members[$i]->member_uid,
        $members[$i]->username
      );

      $u = \BLHelper::get_member_info($members[$i]->member_uid);
      $dt = Carbon::now();

      $newline = "\r\n";
      $affiliate = number_format($m["referrals"]["total_affiliate_available_points"], 2);
      $referral = number_format($m["referrals"]["total_referral_amount"], 2);
      $indirect = number_format($m["indirects"]["total_indirect"], 2);
      $leveling = number_format($m["levelings"]["total_profit"], 2);
      $pairings = number_format($m["pairings"]["Total_Amount"], 2);
      $income = number_format($m["total_structure"], 2);

      $msg = "UPDATE as of " . $dt->format('m-d-Y g:i A') . "! (". strtoupper($u->username) .") ur Affiliate: {$affiliate}" . $newline;
      $msg .= "Referral: {$referral}" . $newline;
      $msg .= "Indirect: {$indirect}" . $newline;
      $msg .= "Leveling: {$leveling}" . $newline;
      $msg .= "Pairings: {$pairings}" . $newline;
      $msg .= "Total Income: {$income} - Thank You! From EnghagePro.com";

      $r = 0;
      if(strlen($u->mobile) == 11) {
        $r = \BLHelper::sms_template($u->mobile, $msg);
      }

      $data[] = [
        "Mobile" => $u->mobile,
        "Message" => $msg,
        "Status" => $r
      ];
    }

    return array(
      "Count" => COUNT($data),
      "Data" => $data
    );
  }

  public function get_member_income($request, $IsMember = null) {
    if(!IsSet($request["account"])) {
      return array(
        "Status" => 404,
        "Message" => "Account parametter did not found"
      );
    }

    $account = $request["account"];
    $members = DB::select("
    SELECT member_uid, username, mobile
    FROM users WHERE member_uid = '{$account}'
    OR username = '{$account}' OR mobile = '{$account}';
    ");

    if(COUNT($members) == 0) {
      return array(
        "Status" => 200,
        "Message" => "Success",
        "Data" => null
      );
    }

    $data = \BLHelper::get_member_structure_details(
      $members[0]->member_uid,
      $members[0]->username
    );

    return array(
      "Status" => 200,
      "Message" => "Success",
      "Data" => $data
    );
  }

  public function get_member_income_info($account) {
    $members = DB::select("
    SELECT member_uid, username, mobile
    FROM users WHERE member_uid = '{$account}'
    OR username = '{$account}' OR mobile = '{$account}';
    ");

    if(COUNT($members) == 0) {
      return 0;
    }

    $m = \BLHelper::get_member_structure_details(
      $members[0]->member_uid,
      $members[0]->username
    );

    $income = (float)$m["total_structure"];
    return $income;
  }


}
