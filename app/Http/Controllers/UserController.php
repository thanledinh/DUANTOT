<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users, 200);
    }
    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        if ($user->is_locked) {
            return response()->json(['message' => 'Tài khoản đã bị khoá '], 403);
        }
        return response()->json($user, 200);
    }   
    
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User fail'], 404);
        }
            $request->validate([
            'username' => 'sometimes|string|max:255|unique:users,username,' . $id,
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string',
            'user_type' => 'sometimes|string|in:user,admin', 
        ]);
            $user->update([
            'username' => $request->username ?? $user->username,
            'email' => $request->email ?? $user->email,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
            'address' => $request->address ?? $user->address,
            'phone_number' => $request->phone_number ?? $user->phone_number,
            'user_type' => $request->user_type ?? $user->user_type,
        ]);
        return response()->json($user, 200);
    }
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User fail'], 404);
        }
        $user->delete();
        return response()->json(['message' => 'Xoá người dùng thành công'], 200);
    }
    public function lockUsers($id)
    {
        $userIds = explode(',', $id);
        $users = User::whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy người dùng nào'], 404);
        }

        foreach ($users as $user) {
            $user->is_locked = true;
            $user->save();
        }

        return response()->json(['message' => 'Khoá tài khoản thành công'], 200);
    }
    public function unlockUsers($id)
    {
        $userIds = explode(',', $id);
        $users = User::whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy người dùng nào'], 404);
        }

        foreach ($users as $user) {
            $user->is_locked = false;
            $user->save();
        }

        return response()->json(['message' => 'Mở khoá tài khoản thành công'], 200);
    }
}
