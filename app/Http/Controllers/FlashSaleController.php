<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use Illuminate\Http\Request;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FlashSaleController extends Controller
{

    //kiểm tra flash sale có hết hạn và gỡ sale khỏi sản phẩm
    public function checkAndRemoveExpiredSales()
    {
        $now = Carbon::now();
        Log::info("Current time: " . $now);

        // Tìm các Flash Sale đã hết hạn
        $expiredSales = FlashSale::where('end_time', '<=', $now)->get();
        Log::info("Found expired sales count: " . $expiredSales->count());

        if ($expiredSales->isEmpty()) {
            Log::info("No expired flash sales found.");
            return response()->json(['message' => 'No expired flash sales to process.']);
        }

        foreach ($expiredSales as $sale) {
            Log::info("Processing sale ID: " . $sale->id);

            // Cập nhật trường sale cho tất cả sản phẩm liên quan
            $flashSaleProducts = FlashSaleProduct::where('flash_sale_id', $sale->id)->get();
            Log::info("Found products for sale ID {$sale->id}: " . $flashSaleProducts->count());

            foreach ($flashSaleProducts as $flashSaleProduct) {
                $product = Product::find($flashSaleProduct->product_id);
                if ($product) {
                    Log::info("Removing sale from product ID: " . $product->id);
                    $product->update(['sale' => 0]);
                } else {
                    Log::warning("Product not found for ID: " . $flashSaleProduct->product_id);
                }
            }

            // Bạn có thể cập nhật trạng thái của Flash Sale nếu cần
            // $sale->update(['status' => 1]);
        }

        return response()->json(['message' => 'Expired flash sales processed successfully.']);
    }

    public function checkAndApplyActiveSales()
    {
        $now = Carbon::now();
        Log::info("Checking active Flash Sales at: " . $now);
    
        // Tìm các Flash Sale đang hoạt động
        $activeSales = FlashSale::active()->get();
        Log::info("Found active sales count: " . $activeSales->count());
    
        if ($activeSales->isEmpty()) {
            Log::info("No active flash sales found.");
            return response()->json(['message' => 'No active flash sales to process.']);
        }
    
        foreach ($activeSales as $sale) {
            Log::info("Processing active sale ID: " . $sale->id);
    
            // Lấy danh sách sản phẩm trong Flash Sale
            $flashSaleProducts = $sale->products;
            Log::info("Found products for active sale ID {$sale->id}: " . $flashSaleProducts->count());
    
            if ($flashSaleProducts->isEmpty()) {
                Log::warning("No products found for active sale ID: " . $sale->id);
            }
    
            foreach ($flashSaleProducts as $flashSaleProduct) {
                $product = Product::find($flashSaleProduct->product_id);
                if ($product) {
                    Log::info("Applying discount to product ID: " . $product->id);
                    $product->update(['sale' => $flashSaleProduct->discount_percentage]);
                } else {
                    Log::warning("Product not found for ID: " . $flashSaleProduct->product_id);
                }
            }
        }
    
        return response()->json(['message' => 'Active flash sales processed successfully.']);
    }
    


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
                        'sale' => 0, // Cập nhật phn trăm giảm giá
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
