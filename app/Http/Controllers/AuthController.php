<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends BaseController
{
    public function register(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Error validation', $validator->errors());
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'user_type' => 'user', // Mặc định là 'user'
        ]);

        $success['user'] = $user;

        return $this->sendResponse($success, 'User registered successfully');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return $this->sendError('Unauthorized', ['error' => 'Unauthorized']);
        }
        $success['token'] = $token;
        return $this->sendResponse($success, 'User login successfully');
    }

    public function logout()
    {
        $success = auth('api')->logout();
        return $this->sendResponse($success, 'User logout successfully');
    }

    public function refresh()
    {
        $success = $this->respondWithToken(auth('api')->refresh());

        return $this->sendResponse($success, 'Token refreshed successfully');
    }

    public function profile()
    {
        $success = auth('api')->user();


        return $this->sendResponse($success, 'User profile retrieved successfully');
    }

    public function updateContactInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $user = Auth::user();
        $updated = false;

        if ($request->has('address')) {
            $user->address = $request->address;
            $updated = true;
        }

        if ($request->has('phone_number')) {
            $user->phone_number = $request->phone_number;
            $updated = true;
        }

        if ($updated) {
            $user->save();
            return $this->sendResponse($user, 'Contact information updated successfully.');
        } else {
            return $this->sendError('No information provided for update.', [], 400);
        }
    }

    public function adminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return $this->sendError('Unauthorized', ['error' => 'Invalid credentials'], 401);
        }

        $user = auth('api')->user();
        if ($user->user_type !== 'admin') {
            auth('api')->logout();
            return $this->sendError('Unauthorized', ['error' => 'You are not authorized to access this area'], 403);
        }

        return $this->createNewToken($token);
    }

    protected function createNewToken($token)
    {
        return $this->sendResponse([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ], 'Admin logged in successfully');
    }


    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ];
    }


    public function forgotPassword(Request $request)
    {
        // Validate the email
        $request->validate(['email' => 'required|email']);
    
        // Attempt to send the password reset link
        $response = Password::sendResetLink($request->only('email'));
    
        // Return a JSON response based on the result
        return $response == Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Password reset link sent to your email.'])
            : response()->json(['message' => 'Unable to send reset link.'], 400);
    }
    


    // Đặt lại mật khẩu
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->sendResponse([], 'Password reset successfully.');
        }

        return $this->sendError('Error', ['email' => __($status)]);
    }







}
