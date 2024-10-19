<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use Illuminate\Http\Request;
use App\Models\Product;

class FlashSaleController extends Controller
{



    // show tất cả flash sale
    public function index()
    {
        $flashSales = FlashSale::all();
        return response()->json([
            'data' => $flashSales
        ], 200);
    }

    // show flash sale theo id
    public function show($id)
    {
        $flashSale = FlashSale::find($id);
        return response()->json([
            'data' => $flashSale
        ], 200);
    }
    // mã show flash sale kèm theo flash sale product
    public function showFlashSaleWithProducts($id)
    {
        $flashSale = FlashSale::with('products')->find($id);
        return response()->json([
            'data' => $flashSale
        ], 200);
    }
    
    public function store(Request $request)
    {
        // Xác thực dữ liệu
        $validated = $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'max_discount' => 'nullable|numeric|min:0',
            'status' => 'required|boolean',
        ]);

        try {
            // Tạo Flash Sale
            $flashSale = FlashSale::create($validated);

            // Trả về JSON thành công
            return response()->json([
                'message' => 'Flash Sale created successfully.',
                'data' => $flashSale
            ], 201);
        } catch (\Exception $e) {
            // Log lỗi để dễ dàng kiểm tra
            \Log::error($e->getMessage());

            return response()->json([
                'message' => 'Có lỗi xảy ra khi tạo Flash Sale.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // Tìm Flash Sale
        $flashSale = FlashSale::find($id);
        if (!$flashSale) {
            return response()->json(['message' => 'Flash Sale không tồn tại.'], 404);
        }

        // Xác thực dữ liệu
        $validated = $request->validate([
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after:start_time',
            'max_discount' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        try {
            // Cập nhật Flash Sale
            $flashSale->update($validated);

            // Trả về JSON thành công
            return response()->json([
                'message' => 'Flash Sale updated successfully.',
                'data' => $flashSale
            ], 200);
        } catch (\Exception $e) {
            // Log lỗi
            \Log::error($e->getMessage());

            return response()->json([
                'message' => 'Có lỗi xảy ra khi cập nhật Flash Sale.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        // Tìm Flash Sale
        $flashSale = FlashSale::find($id);
        if (!$flashSale) {
            return response()->json(['message' => 'Flash Sale không tồn tại.'], 404);
        }

        try {
            // Cập nhật trường sale cho tất cả sản phẩm liên quan trước khi xóa
            $flashSaleProducts = FlashSaleProduct::where('flash_sale_id', $flashSale->id)->get(); // Lấy tất cả sản phẩm liên quan
            foreach ($flashSaleProducts as $flashSaleProduct) {
                $product = Product::find($flashSaleProduct->product_id);
                if ($product) {
                    $product->update([
                        'sale' => 0, // Cập nhật phần trăm giảm giá
                    ]);
                }
            }


            // Xóa Flash Sale
            $flashSale->delete();

            return response()->json(['message' => 'Flash Sale deleted successfully.'], 200);

        } catch (\Exception $e) {
            // Log lỗi
            \Log::error($e->getMessage());

            return response()->json([
                'message' => 'Có lỗi xảy ra khi xóa Flash Sale.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // show flash sale theo ngày
    public function showFlashSaleByDate(Request $request)
    {
        $date = $request->input('date'); // Lấy ngày từ request

        // Kiểm tra xem ngày có hợp lệ không
        if (!$date || !strtotime($date)) {
            return response()->json(['message' => 'Ngày không hợp lệ.'], 400);
        }

        // Lấy flash sale theo ngày
        $flashSales = FlashSale::whereDate('start_time', '<=', $date)
            ->whereDate('end_time', '>=', $date)
            ->get();

        // Chỉ lấy start_time và end_time từ flash sales
        $flashSalesData = $flashSales->map(function ($flashSale) {
            return [
                'id' => $flashSale->id,
                'start_time' => $flashSale->start_time,
                'end_time' => $flashSale->end_time,
            ];
        });

        return response()->json([
            'data' => $flashSalesData
        ], 200);
    }

}
