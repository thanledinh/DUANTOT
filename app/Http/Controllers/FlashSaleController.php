<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FlashSaleController extends Controller
{
    public function store(Request $request)
    {
        // if (!Gate::allows('isAdmin')) {
        //     return response()->json([
        //         'message' => 'Bạn không có quyền thực hiện hành động này.'
        //     ], 403);
        // }
        $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'discount_percentage' => 'required|integer|min:0|max:100',
            'max_discount' => 'nullable|numeric|min:0',
            'status' => 'required|boolean',
        ]);
        try {
            $flashSale = FlashSale::create($request->all());

            return response()->json([
                'message' => 'Flash Sale created successfully.',
                'data' => $flashSale
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi tạo Flash Sale.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        // if (!Gate::allows('isAdmin')) {
        //     return response()->json([
        //         'message' => 'Bạn không có quyền thực hiện hành động này.'
        //     ], 403);
        // }
        $flashSale = FlashSale::find($id);
        if (!$flashSale) {
            return response()->json(['message' => 'Flash Sale không tồn tại.'], 404);
        }
        $request->validate([
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'max_discount' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        try {
            $flashSale->update($request->all());

            return response()->json([
                'message' => 'Flash Sale updated successfully.',
                'data' => $flashSale
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật Flash Sale.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy($id)
    {
        // if (!Gate::allows('isAdmin')) {
        //     return response()->json([
        //         'message' => 'Bạn không có quyền thực hiện hành động này.'
        //     ], 403);
        // }
        $flashSale = FlashSale::find($id);
        if (!$flashSale) {
            return response()->json(['message' => 'Flash Sale không tồn tại.'], 404);
        }
        try {
            $flashSale->delete();

            return response()->json([
                'message' => 'Flash Sale deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Có lỗi xảy ra khi xóa Flash Sale.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
