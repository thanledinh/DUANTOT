<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PromotionController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|unique:promotions,code',
            'description' => 'nullable|string',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'discount_amount' => 'nullable|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'promotion_type' => 'required|string',
        ]);

        $promotion = Promotion::create($data);
        return response()->json($promotion, 201);
    }

    public function check(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string',
        ]);
    
        $current_time = Carbon::now();
        $promotion = Promotion::where('code', $data['code'])
            ->where('start_date', '<=', $current_time)
            ->where('end_date', '>=', $current_time)
            ->first();
    
        if (!$promotion) {
            return response()->json([
                'message' => 'Mã khuyến mãi không hợp lệ hoặc đã hết hạn',
                'current_time' => $current_time,
                'promotion_start' => $promotion ? $promotion->start_date : null,
                'promotion_end' => $promotion ? $promotion->end_date : null,
            ], 404);
        }
    
        return response()->json($promotion, 200);
    }
    


}
