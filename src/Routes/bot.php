<?php


use Illuminate\Http\Request;

Route::any('/bloops/bot/v1/daily-update/init', function(Request $request) {
  return BLBot::init($request);
});

Route::any('/bloops/bot/v1/member-income', function(Request $request) {
  return BLBot::get_member_income($request);
});
