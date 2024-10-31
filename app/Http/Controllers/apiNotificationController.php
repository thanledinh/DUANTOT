<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;


class apiNotificationController extends Controller
{
    //

    public function showAllNotifications()
    {

        // Lấy tất cả thông báo
        $notifications = Notification::all();

        return response()->json($notifications, 200);
    }



    public function createNotification(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'user_emails' => 'required|array', // Mảng email người dùng nhận thông báo
            'user_emails.*' => 'email', // Kiểm tra xem email có hợp lệ không
        ]);

        // Tạo thông báo mới và lưu vào bảng notifications
        $notification = Notification::create([
            'message' => $request->message,
        ]);

        $notFoundEmails = []; // Mảng để lưu các email không tồn tại

        // Gán thông báo cho người dùng trong bảng user_notifications
        foreach ($request->user_emails as $email) {
            // Tìm người dùng theo email
            $user = User::where('email', $email)->first();

            if ($user) {
                // Gán thông báo cho người dùng
                $userNotificationData = [
                    'status' => true, // 1 đã gửi 0 là chưa gửi
                    'read_status' => false, // 1 là đọc rồi 0 là chưa
                    'important' => $request->important ?? false, // 1 là quan trọng
                ];

                // Thêm vào bảng user_notifications
                $notification->users()->attach($user->id, $userNotificationData);

                // Gửi email cho người dùng
                Mail::raw($request->message, function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Thông báo mới'); // Tiêu đề email
                });
            } else {
                // Thêm email không tồn tại vào mảng
                $notFoundEmails[] = $email;
            }
        }

        // Trả về phản hồi với mã trạng thái 200
        return response()->json([
            'message' => 'Thông báo đã được gửi thành công.',
            'not_found_emails' => !empty($notFoundEmails) ? $notFoundEmails : null // Trả về danh sách email không tồn tại nếu có
        ], 200);
    }



    // Lấy danh sách thông báo của người dùng
    public function getUserNotifications()
    {
        $notifications = Auth::user()->notifications()->get();
        return response()->json($notifications, 200);
    }

    // Đánh dấu thông báo là đã đọc
    public function markAsRead($id)
    {
        // Tìm thông báo trong bảng user_notifications
        $userNotification = UserNotification::where('notification_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Đánh dấu là đã đọc
        $userNotification->read_status = true;
        $userNotification->save();

        return response()->json(['message' => 'Thông báo đã được đánh dấu là đã đọc.'], 200);
    }

    // Xóa thông báo
    public function destroy($id)
    {
        // Tìm thông báo
        $notification = Notification::findOrFail($id);

            $notification->delete();
            return response()->json(['message' => 'Thông báo đã được xóa thành công.'], 200);
     
        // Người dùng chỉ có thể xóa thông báo của chính họ
        $userNotification = UserNotification::where('notification_id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$userNotification) {
            return response()->json(['message' => 'Bạn không có quyền xóa thông báo này.'], 403);
        }
        // Xóa thông báo của người dùng
        $userNotification->delete();

        return response()->json(['message' => 'Thông báo của bạn đã được xóa thành công.'], 200);

    }
}
