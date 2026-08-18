<?php
namespace App\Http\Requests\Api;
use Illuminate\Foundation\Http\FormRequest;
class CloseShiftRequest extends FormRequest { public function authorize(): bool{return true;} public function rules(): array{return ['actual_cash'=>['required','integer','min:0'],'notes'=>['nullable','string','max:1000']];} }
