<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        // Xác thực email
        $request->validate(['email' => 'required|email']);

        // Tạo mã OTP ngẫu nhiên (6 chữ số)
        $otp = rand(100000, 999999); // Tạo mã OTP từ 100000 đến 999999

        // Tính thời gian hết hạn (ví dụ: 5 phút)
        $expiresAt = Carbon::now()->addMinutes(5);

        // Lưu mã OTP vào bảng otps
        DB::table('otps')->insert([
            'email' => $request->email,
            'otp' => $otp,
            'expires_at' => $expiresAt,
            'created_at' => now(),
        ]);

        // Gửi mã OTP đến email của người dùng
        Mail::raw("Mã OTP của bạn để đặt lại mật khẩu là: $otp", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Mã OTP Đặt Lại Mật Khẩu');
        });

        return response()->json(['message' => 'Mã OTP đã được gửi đến email của bạn.']);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|integer', // Xác thực mã OTP là số
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed', // Xác thực mật khẩu và xác nhận
        ]);

        if ($validator->fails()) {
            return $this->sendError('Lỗi xác thực.', $validator->errors());
        }

        // Kiểm tra mã OTP trong bảng otps
        $otpRecord = DB::table('otps')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>', now()) // Kiểm tra xem OTP có còn hiệu lực không
            ->first();

        if (!$otpRecord) {
            return $this->sendError('Mã OTP không hợp lệ hoặc đã hết hạn.', [], 400);
        }

        // Đặt lại mật khẩu
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->sendError('Người dùng không tồn tại.', [], 404);
        }

        $user->password = Hash::make($request->password);
        $user->setRememberToken(Str::random(60));
        $user->save();

        // Xóa mã OTP khỏi bảng otps
        DB::table('otps')->where('id', $otpRecord->id)->delete();

        return $this->sendResponse([], 'Mật khẩu đã được đặt lại thành công.');
    }

    public function changePassword(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|confirmed', // Xác thực mật khẩu mới và xác nhận
        ]);

        if ($validator->fails()) {
            return $this->sendError('Lỗi xác thực.', $validator->errors());
        }

        // Lấy người dùng hiện tại
        $user = Auth::user();

        // Kiểm tra mật khẩu cũ
        if (!Hash::check($request->current_password, $user->password)) {
            return $this->sendError('Mật khẩu cũ không chính xác.', [], 400);
        }

        // Cập nhật mật khẩu mới
        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->sendResponse([], 'Mật khẩu đã được thay đổi thành công.');
    }
}
