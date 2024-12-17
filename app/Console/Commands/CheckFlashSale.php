<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FlashSale;
use App\Models\FlashSaleProduct;
use App\Models\Product;
use Carbon\Carbon;

class CheckFlashSale extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flashsale:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check flash sales and perform actions based on start and end times';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // Kiểm tra các Flash Sale đã bắt đầu
        $startingSales = FlashSale::where('start_time', '<=', $now)
                                  ->where('end_time', '>', $now)
                                  ->where('status', 1)
                                  ->get();

        foreach ($startingSales as $sale) {
            $sale->update(['status' => 0]);
            // Thực hiện các hành động khi Flash Sale bắt đầu
        }

        // Kiểm tra các Flash Sale đã kết thúc
        $endingSales = FlashSale::where('end_time', '<=', $now)
                                ->where('status', 0)
                                ->get();

        foreach ($endingSales as $sale) {
            // Gỡ bỏ giảm giá cho các sản phẩm trong Flash Sale
            $flashSaleProducts = FlashSaleProduct::where('flash_sale_id', $sale->id)->get();
            foreach ($flashSaleProducts as $flashSaleProduct) {
                $product = Product::find($flashSaleProduct->product_id);
                if ($product) {
                    $product->update(['sale' => 0]); // Gỡ bỏ giảm giá
                }
            }

            $sale->update(['status' => 1]); // Cập nhật trạng thái Flash Sale
        }
    }
}
