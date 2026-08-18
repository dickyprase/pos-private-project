<?php
namespace App\Http\Resources; use Illuminate\Http\Request; use Illuminate\Http\Resources\Json\JsonResource;
class PaymentResource extends JsonResource { public static $wrap=null; public function toArray(Request $request): array{return ['id'=>$this->id,'order_id'=>$this->order_id,'method'=>$this->method->value,'status'=>$this->status->value,'amount'=>$this->amount,'received_amount'=>$this->received_amount,'change_amount'=>$this->change_amount,'reference_number'=>$this->reference_number,'paid_at'=>$this->paid_at?->toISOString()];} }
