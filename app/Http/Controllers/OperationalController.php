<?php

namespace App\Http\Controllers;

use App\Facades\MessageDakama;
use App\Models\OperationalHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OperationalController extends Controller
{
    public function show()
    {
        $operational = OperationalHour::first();
        if (!$operational) {
            return MessageDakama::warning("Operational hour not found!");
        }

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'message' => "Operational hour found!",
            "data" => $operational
        ]);
    }

    public function save(Request $request)
    {
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'ontime_start' => 'required|date_format:H:i:s',
            'ontime_end' => 'required|date_format:H:i:s',
            'late_time' => 'required|date_format:H:i:s',
            'offtime' => 'required|date_format:H:i:s',
            'bonus' => 'required|integer',
            'duration' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors()
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $operational = OperationalHour::first();
            if ($operational) {
                $operational->update($validator->validated());
            } else {
                OperationalHour::create($validator->validated());
            }

            DB::commit();
            return MessageDakama::success("Operational hour saved!");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }
}
