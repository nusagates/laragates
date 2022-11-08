<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Nusagates\Helper\Responses;

class AuthController extends Controller
{
    public function login(Request $request)
    {
    }

    public function register(Request $request)
    {
        $rules = [
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|min:3|max:25',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required'
        ];
        $validation = Validator::make($request->post(), $rules);
        if ($validation->fails()) {
            return Responses::showValidationError($validation);
        }
        $user = User::create([
            'email' => $request->email,
            'name' => $request->name,
            'password' => bcrypt($request->password),
        ]);
        return Responses::showSuccessMessage("Your account has been created", $user);
    }
}
