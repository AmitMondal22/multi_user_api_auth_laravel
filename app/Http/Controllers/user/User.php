<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Mail\Subscribe;
use App\Models\User as ModelsUser;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class User extends Controller
{
    // ================== Send Email ============================================
    public function send_mail_opt($email, $otp)
    {
        $details = [
            'title' => 'Dear Customer',
            'body' => "Welcome, We thank you for your registration at MySchool Online school system  Your One Time Password is : " . $otp,
            'subject' => 'Your email id Verification activation OTP code',
            'send_by'=>'Amit Mondal'
        ];
        return Mail::to($email)->send(new Subscribe($details));
    }

    // ======================= Create Account ======================================
    public function create_account(Request $r){
        $otp = sprintf("%06d", mt_rand(000001, 999999));

        $rules=[
            'name'=>'required',
            'email'=>'required|email',
            'mobile'=>'required|numeric',
            'password'=>'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
        ];
        $valaditor=Validator::make($r->all(),$rules);
        if($valaditor->fails()){
            return response()->json($valaditor->errors(),400); //400 envalies responce
        }
        $data=ModelsUser::create([
            'name'=>$r->name,
            'email'=>$r->email,
            'mobile_no'=>$r->mobile,
            'password'=>Hash::make($r->password),
            'role'=>'U',
            'otp'=>$otp,
            'otp_status'=>'D',
        ]);

        $this->send_mail_opt($r->email, $otp);
        return response()->json($data,201);

    }

    // =======================otp validation ====================
    public function otp_validation(Request $r){
        $rules=[
            'id'=>'required',
            'email'=>'required|email',
            'mobile'=>'required|numeric',
            'otp'=>'required|integer|digits:6',
        ];
        $valaditor=Validator::make($r->all(),$rules);
        if($valaditor->fails()){
            return response()->json($valaditor->errors(),401); //400 envalies responce
        }

        if($r->otp==="000000"||$r->otp===''||$r->otp===null||$r->otp===000000){
            return response()->json(['error'=>'The OTP format is invalid.'],401);
        }else{
            $data = ModelsUser::where('id', $r->id)
                            ->where('email', $r->email)
                            ->where('mobile_no', $r->mobile)
                            ->where('otp', $r->otp)
                            ->update(['otp_status' => 'A','otp'=>000000]);
            if ($data==1) {
                $data2 = ModelsUser::where('id', $r->id)
                            ->where('email', $r->email)
                            ->where('mobile_no', $r->mobile)
                            ->where('otp_status', 'A')
                            ->select('id','name','email','mobile_no','role')
                            ->first();

                return response()->json($data2,201);
            } else {
                return response()->json(['error'=>'Invalid OTP Code'],401);
            }
        }

    }
    // ================= send otp=====================
    public function resend_otp(Request $r){
        $otp = sprintf("%06d", mt_rand(000001, 999999));

        $rules=[
            'email'=>'required|email',
        ];
        $valaditor=Validator::make($r->all(),$rules);
        if($valaditor->fails()){
            return response()->json($valaditor->errors(),401); //400 envalies responce
        }
        $data=ModelsUser::where('email', $r->email)->update(['otp_status' => 'D','otp'=>$otp]);
        if ($data==1) {
            $this->send_mail_opt($r->email, $otp);
            $data2 = ModelsUser::where('email', $r->email)
                        ->where('otp_status', 'D')
                        ->select('id','name','email','mobile_no','role')
                        ->first();
            return response()->json($data2,201);
        } else {
            return response()->json(['error'=>'Invalid OTP Code'],201);
        }
    }
    // ====================create new password ================================
    public function new_password(Request $r){
        $rules=[
            'email'=>'required|email',
            'otp'=>'required|integer|digits:6',
            'password'=>'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
        ];
        $valaditor=Validator::make($r->all(),$rules);
        if($valaditor->fails()){
            return response()->json($valaditor->errors(),401); //400 envalies responce
        }

        $data=ModelsUser::where('id', $r->id)->where('email', $r->email)->where('otp', $r->otp)
            ->update(['otp_status' => 'A','otp'=>000000,'password'=>Hash::make($r->password)]);
        if ($data==1) {
            $data2 = ModelsUser::where('id', $r->id)
                        ->where('email', $r->email)
                        ->where('otp_status', 'A')
                        ->select('id','name','email','mobile_no','role')
                        ->first();
            return response()->json([
                    'status' => true,
                    'message' => 'Password Change Successfully',
                    'user' => $data2,
                    'token' => $data2->createToken("API TOKEN")->plainTextToken
                ], 200);
        } else {
            return response()->json(['error'=>'Invalid OTP Code'],201);
        }
    }

    // =======================Login========================
    public function login(Request $r){
        $rules=[
            'email'=>'required|email',
            'password'=>'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
        ];
        $valaditor=Validator::make($r->all(),$rules);
        if($valaditor->fails()){
            return response()->json($valaditor->errors(),401); //400 envalies responce
        }

        $data = ModelsUser::where('email', $r->email)->where('otp_status', 'A')->first();

        if (!empty($data)) {
            if(Hash::check($r->password, $data->password)){
            return response()->json([
                'status' => true,
                'message' => 'Password Change Successfully',
                'user' => $data,
                'token' => $data->createToken($data->name, ['U'])->plainTextToken
            ], 200);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'password not match',

            ], 401);
        }
        }else{
            return response()->json([
                'status' => true,
                'message' => 'Logout Successfully',

            ], 401);
        }
    }

    public function logout(Request $r){
        $r->user()->currentAccessToken()->delete();
        return [
            'message' => 'user logged out'
        ];
    }

    public function getview(){
        $data=[
            'hello'=>'test',
            'hello2'=>'test2',
        ];
        return auth()->user();
    }


}
