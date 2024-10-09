<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function create(Request $request)
    {
        $promotion = Promotion::create($request->all());
        return response()->json(['promotion' => $promotion], 201);
    }
    public function check(Request $request)
    {
        $promotion = Promotion::where('code', $request->input('code'))
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();
        if ($promotion && $promotion->isValidForOrder($request->order)) {
            return response()->json(['valid' => true, 'promotion' => $promotion]);
        }
        return response()->json(['valid' => false], 404);
    }
  
}
