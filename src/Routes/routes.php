<?php


use Illuminate\Http\Request;

Route::get('/bloops/demo', function() {
  $html = "<html>
              <head>
                  <title>Welcome | king052188/BinaryLoops</title>
              </head>
              <body style='text-align: center;'>
                <h3 style='margin: 300px 0 0 0;'>*** Well Done! You are good to go ***</h3>
                <p>@kingpauloaquino | kingpauloaquino@gmail.com</p>
                <p><a href='http://kpa.ph/kingpauloaquino'>kpa.ph/kingpauloaquino</a></p>
              </body>
          </html>";
  return $html;
});

Route::get('/bloops/info/{all?}', function($all = null) {
  $a = false;
  if($all != null) {
    $a = true;
  }
  return BinaryLoops::TestServices($a);
});

//

Route::any('/bloops/v1/encode', function(Request $request) {
  return BinaryLoops::Encode($request);
});

Route::any('/bloops/v1/placement-validation', function(Request $request) {
  return BinaryLoops::Placement_Validate($request);
});

Route::any('/bloops/v1/member-pairing-status/{member_uid}', function($member_uid) {
  return BinaryLoops::Member_Pairing($member_uid);
});

Route::any('/bloops/v1/member-structure-details/{member_uid}', function($member_uid) {
  return BinaryLoops::Member_Structure_Details($member_uid);
});

Route::any('/bloops/v1/populate-genealogy/{username}', function($username) {
  return BinaryLoops::Populate_Genealogy($username);
});

Route::any('/bloops/v1/populate-leveling/{username}', function($username) {
  return BinaryLoops::Populate_Leveling($username);
});

Route::any('/bloops/v1/populate-indirect/{member_uid}', function($member_uid) {
  return BinaryLoops::Populate_Indirect($member_uid);
});

Route::any('/bloops/v1/populate-leveling/{username}/{position}/{level}', function($username, $position, $level) {
  return BLHelper::get_count_pairing_per_level($username, $position, $level);
});

Route::any('/bloops/v1/populate-multiple-accounts/{member_uid}/{mobile}/{limit?}', function($username, $mobile) {
  return BinaryLoops::Populate_Multiple_Accounts($username, $mobile);
});

Route::any('/bloops/v1/generate-activation-code', function(Request $request) {
  return BinaryLoops::Generate_Activation_Code($request);
});

Route::any('/bloops/v1/forward-lookup/directs/{username}', function($username) {
  $data = BLHelper::lookup_directs($username);

  return view('sample.direct', compact('data'));
});

Route::any('/bloops/v1/sample/direct', function() {
  return view('sample.direct');
});
