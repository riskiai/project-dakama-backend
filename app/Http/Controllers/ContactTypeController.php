<?php

namespace App\Http\Controllers;

use App\Models\ContactType;
use Illuminate\Http\Request;
use App\Facades\MessageDakama;

class ContactTypeController extends Controller
{
    public function index()
    {
        $contactTypes = ContactType::get();

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'data' => $contactTypes
        ]);
    }

    public function show($id)
    {
        $contactType = ContactType::find($id);
        if (!$contactType) {
            return MessageDakama::notFound('data not found!');
        }

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'data' => $contactType
        ]);
    }
}
