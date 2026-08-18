<?php
namespace App\Http\Requests\Api;
use Illuminate\Foundation\Http\FormRequest;
class TokenRequest extends FormRequest { public function authorize(): bool{return true;} public function rules(): array{return ['login'=>['required','string'],'password'=>['required','string'],'device_name'=>['nullable','string','max:100']];} }
