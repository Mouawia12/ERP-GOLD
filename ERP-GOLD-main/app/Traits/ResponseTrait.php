<?php
  
namespace App\Traits;
  
use Illuminate\Http\Request;
  
trait ResponseTrait {
  
    public function response($response,$code,$result = null) {
         $flag = ($code == 200) ? 0 : 1;
         return response()->json( [
         'error_flag'    => $flag,
         'message'       => $response,
         'result'        => $result,
            ] , $code, [], JSON_UNESCAPED_UNICODE);
        }
  
}