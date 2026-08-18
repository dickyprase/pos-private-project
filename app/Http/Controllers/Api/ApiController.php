<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; use Illuminate\Http\JsonResponse;
abstract class ApiController extends Controller { protected function success(mixed $data=null,?string $message=null,int $status=200): JsonResponse{return response()->json(['success'=>true,'data'=>$data,'message'=>$message],$status);} }
