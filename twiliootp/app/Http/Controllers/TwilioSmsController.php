<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\VerifyNumber;
use Exception;
use Setting;
use Twilio;
use Twilio\Rest\Client;
use Log;

class TwilioSmsController extends Controller
{
    public function smsSentOTP(Request $request){
    	
       $this->validate($request, [
         
                'mobile' => 'required|unique:users',
                'email' => 'required|email|max:255|unique:users',
                
            ]);

    	$mobileOTP = $this->generateOTP();

    	$request['otp_code'] = $mobileOTP; 

    	try{
			$MsgBody = Setting::get('site_title', 'YoloCabs');
			$MsgBody = $MsgBody." Registeration OTP ".$mobileOTP;
			$accountSid = env('TWILIO_SID');
			$authToken = env('TWILIO_TOKEN');
			$twilioNumber = env('TWILIO_FROM');
			$to = $request->mobile;
			$client = new Client($accountSid, $authToken);
			try {
				$client->messages->create(
					$to,
					[
						"body" => $MsgBody,
						"from" => $twilioNumber
					]);
			    Log::info('Message sent to ' . $to);
			}catch (TwilioException $e) {
				Log::error(
					'Could not send SMS notification.' .
					' Twilio replied with: ' . $e
					);
			}
			$VerifyNumber['mobile'] = $request->mobile;
			$VerifyNumber['country_code'] = $request->country_code;
			$VerifyNumber['otp_code'] = $mobileOTP;
			$VerifyNumber['status'] = 'VERIFY';

			$Verify = VerifyNumber::create($VerifyNumber);

			if($Verify){
				if($request->ajax()) {
                return response()->json(['status'=>true,'message' => trans('api.user.otp_sent_success')]);
	            }else{
	                return back()->with('flash_success', trans('api.user.otp_sent_success'));
	            }
			}else{
				if($request->ajax()) {
                	return response()->json(['status'=>false,'message' => trans('api.user.otp_sent_fail')]);
	            }else{
	                return back()->with('flash_error', trans('api.user.otp_sent_fail'));
	            }
			}

		} catch (Exception $e) {
			if($request->ajax()) {
                return response()->json(['status'=>false,'message' =>'Mobile number is invalid !']);
            }else{
                return back()->with('flash_error', $e->getMessage());
            }
        }
    }

    public function generateOTP(){
    	return substr(str_shuffle("0123456789"), 0, 6);
    }
}
