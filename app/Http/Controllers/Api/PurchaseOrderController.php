<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return PurchaseOrder::paginate()->toResourceCollection();
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     return response()->json([
    //         'message' => 'Purchase order created successfully.'
    //     ], 201);
    // }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return PurchaseOrder::findOrFail($id)->toResource();
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     return response()->json([
    //         'message' => "Purchase order with ID: {$id} updated successfully."
    //     ]);
    // }

    // /**
    //  * Remove the specified resource from storage.
    //  */
    // public function destroy(string $id)
    // {
    //     return response()->json([
    //         'message' => "Purchase order with ID: {$id} deleted successfully."
    //     ]);
    // }
}
