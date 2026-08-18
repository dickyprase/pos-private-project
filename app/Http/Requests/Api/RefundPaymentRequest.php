<?php
namespace App\Http\Requests\Api;
use Illuminate\Foundation\Http\FormRequest;
class RefundPaymentRequest extends FormRequest { public function authorize(): bool{return true;} public function rules(): array{return ['reason'=>['required','string','min:5','max:500'],'approved_by'=>['required','integer','exists:users,id'],'pin'=>['required','string'],'restock'=>['boolean']];} }
