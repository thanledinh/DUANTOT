<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use Illuminate\Http\Request;

class FlashSaleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'discount_percentage' => 'required|integer',
            'max_discount' => 'nullable|numeric',
            'status' => 'required|boolean',
        ]);

        $flashSale = FlashSale::create($request->all());

        return response()->json([
            'message' => 'Flash Sale created successfully.',
            'data' => $flashSale
        ], 201);
    }
}
