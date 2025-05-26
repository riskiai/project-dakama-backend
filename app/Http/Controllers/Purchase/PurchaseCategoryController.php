<?php

namespace App\Http\Controllers\Purchase;

use App\Facades\MessageActeeve;
use App\Facades\MessageDakama;
use App\Models\PurchaseCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PurchaseCategoryController extends Controller
{
    public function index()
    {
        $purchaseCategories = PurchaseCategory::all();

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'data' => $purchaseCategories
        ]);
    }

    public function show($id)
    {
        $purchaseCategory = PurchaseCategory::find($id);
        if (!$purchaseCategory) {
            return MessageDakama::notFound('data not found!');
        }

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'data' => $purchaseCategory
        ]);
    }
}
