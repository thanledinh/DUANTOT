<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
class AdminUserController extends Controller
{
    //
    public function index()
    {
        $users = User::orderBy('created_at', 'desc') // Sắp xếp theo thời gian tạo
            ->get();
        return response()->json($users);
    }


    public function show($id)
    {

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Tài khoản không có'], 404);
        }
        return response()->json($user);
    }

    public function store(Request $request)
    {

        // Xác thực dữ liệu đầu vào
        $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed', // Xác nhận mật khẩu
            'address' => 'nullable|string|max:255', // Xác thực cho address
            'phone_number' => 'nullable|string|max:15', // Xác thực cho phone_number
            'user_type' => 'nullable|string|in:user,admin'
        ]);

        // Tạo người dùng mới
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => md5($request->password), // Mã hóa mật khẩu bằng MD5
            'address' => $request->address, // Thêm address
            'phone_number' => $request->phone_number, // Thêm phone_number
            'user_type' => $request->user_type ?? 'user',
            'status' => 'active', // Mặc định là 'active'
        ]);

      
        return response()->json($user, 201);
    }


    public function updateStatus(Request $request, $id)
    {
        // Xác thực dữ liệu đầu vào
        $request->validate([
            'status' => 'required|string|in:active,inactive',
        ]);

       
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->status = $request->status;
        $user->save();
        return response()->json($user);
    }

    // tìm người dùng theo tên 
    public function searchUserByName(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
        ]);

        $user = User::where('username', 'like', '%' . $request->username . '%')->get();
        return response()->json($user);
    }

    ///api/users/{userId}/orders
    public function getOrdersByUser($userId)
    {
        $orders = Order::where('user_id', $userId)->get();
        return response()->json($orders);
    }
}
