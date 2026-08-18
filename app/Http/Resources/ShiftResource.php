<?php
namespace App\Http\Resources; use Illuminate\Http\Request; use Illuminate\Http\Resources\Json\JsonResource;
class ShiftResource extends JsonResource { public static $wrap=null; public function toArray(Request $request): array{return ['id'=>$this->id,'cashier_id'=>$this->cashier_id,'status'=>$this->status->value,'opened_at'=>$this->opened_at?->toISOString(),'closed_at'=>$this->closed_at?->toISOString(),'opening_cash'=>$this->opening_cash,'expected_cash'=>$this->expected_cash,'actual_cash'=>$this->actual_cash,'difference'=>$this->difference,'notes'=>$this->notes];} }
