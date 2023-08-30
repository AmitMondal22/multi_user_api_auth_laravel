<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User as ModelsUser;
use Illuminate\Support\Facades\Hash;

class SellersLabel extends Controller
{
    function createSellers(Request $r){
        try {
            $otp = sprintf("%06d", mt_rand(000001, 999999));

            $rules = [
                'name' => 'required',
                'email' => 'required|email',
                'mobile' => 'required|numeric',
                'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
            ];
            $valaditor = Validator::make($r->all(), $rules);
            if ($valaditor->fails()) {
                return response()->json($valaditor->errors(), 400); //400 envalies responce
            }
            $data = ModelsUser::create([
                'name' => $r->name,
                'email' => $r->email,
                'mobile_no' => $r->mobile,
                'password' => Hash::make($r->password),
                'role' => 'U',
                'otp' => $otp,
                'otp_status' => 'D',
                "sellers_id"=>1,
                "sellers_type"=>"D",
                "business_id"=>1,
                "user_type"=>"A",
                "created_at"=>auth()->user()->id,
                "deleted_flag"=>"N",
                "created_by"=>1
            ]);

            // $this->send_mail_opt($r->email, $otp);
            return response()->json($data, 200);
        } catch (\Throwable $th) {
            //throw $th;
            $data = [
                "ERROR" => $th,
                "STATUS" => 0,
            ];
            return response()->json($data, 401);
        }
    }
}
