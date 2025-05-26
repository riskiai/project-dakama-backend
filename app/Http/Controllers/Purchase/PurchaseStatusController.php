<?php

namespace App\Http\Controllers\Purchase;

use App\Facades\MessageActeeve;
use App\Facades\MessageDakama;
use App\Models\PurchaseStatus;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PurchaseStatusController extends Controller
{
    public function index()
    {
        $purchaseStatus = PurchaseStatus::whereNotIn('id', [PurchaseStatus::VERIFIED])->get();

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'data' => $purchaseStatus
        ]);
    }

    public function show($id)
    {
        $purchaseStatus = PurchaseStatus::find($id);
        if (!$purchaseStatus) {
            return MessageDakama::notFound('data not found!');
        }

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'data' => $purchaseStatus
        ]);
    }
}
