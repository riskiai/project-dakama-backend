<?php

namespace App\Http\Controllers;

use App\Facades\MessageDakama;
use App\Models\OperationalHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OperationalController extends Controller
{
    /* public function show()
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
    } */

        /** List semua shift (paginate) */
    public function index(Request $request)
    {
        $perPage = (int) ($request->get('per_page', 10));
        $data = OperationalHour::query()
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'message' => 'Operational hours fetched.',
            'data' => $data,
        ], MessageDakama::HTTP_OK);
    }

    public function indexall()
    {
        $data = OperationalHour::all();

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'message' => 'Operational hours fetched.',
            'data' => $data,
        ], MessageDakama::HTTP_OK);
    }

    /** Detail 1 shift by id */
    public function show($id)
    {
        $row = OperationalHour::find($id);
        if (!$row) {
            return MessageDakama::warning('Operational hour not found!', MessageDakama::HTTP_NOT_FOUND);
        }

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'message' => 'Operational hour found!',
            'data' => $row,
        ], MessageDakama::HTTP_OK);
    }

    public function save(Request $request)
    {
        DB::beginTransaction();

        $validator = Validator::make($request->all(), [
            'ontime_start' => 'required|date_format:H:i:s',
            'ontime_end'   => 'required|date_format:H:i:s',
            'late_time'    => 'required|date_format:H:i:s',
            'offtime'      => 'required|date_format:H:i:s',
            // 'bonus'        => 'required|integer',
            // 'duration'     => 'required|integer',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status' => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message' => $validator->errors(),
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // langsung create tanpa cek first()
            $operational = OperationalHour::create($validator->validated());

            DB::commit();
            return MessageDakama::render([
                'status' => MessageDakama::SUCCESS,
                'status_code' => MessageDakama::HTTP_CREATED,
                'message' => 'Operational hour created!',
                'data' => $operational
            ], MessageDakama::HTTP_CREATED);

        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }


    public function update(Request $request, $id)
    {
        $row = OperationalHour::find($id);
        if (!$row) {
            return MessageDakama::warning('Operational hour not found!', MessageDakama::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'ontime_start' => 'sometimes|required|date_format:H:i:s',
            'ontime_end'   => 'sometimes|required|date_format:H:i:s',
            'late_time'    => 'sometimes|required|date_format:H:i:s',
            'offtime'      => 'sometimes|required|date_format:H:i:s',
            // 'bonus'        => 'sometimes|required|integer',
            // 'duration'     => 'sometimes|required|integer',
        ]);

        if ($validator->fails()) {
            return MessageDakama::render([
                'status'      => MessageDakama::WARNING,
                'status_code' => MessageDakama::HTTP_UNPROCESSABLE_ENTITY,
                'message'     => $validator->errors(),
            ], MessageDakama::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction();

            $row->update($validator->validated());

            DB::commit();
            return MessageDakama::render([
                'status'      => MessageDakama::SUCCESS,
                'status_code' => MessageDakama::HTTP_OK,
                'message'     => 'Operational hour updated.',
                'data'        => $row->fresh(),
            ], MessageDakama::HTTP_OK);

        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    /** Hapus 1 shift by id (soft delete aktif di model) */
    public function destroy($id)
    {
        $row = OperationalHour::find($id);
        if (!$row) {
            return MessageDakama::warning('Operational hour not found!', MessageDakama::HTTP_NOT_FOUND);
        }

        try {
            $row->delete(); // Soft delete
            return MessageDakama::success('Operational hour deleted.');
        } catch (\Throwable $th) {
            return MessageDakama::error($th->getMessage());
        }
    }

     
    public function restore($id)
    {
        $row = OperationalHour::onlyTrashed()->find($id);
        if (!$row) {
            return MessageDakama::warning('Operational hour (trashed) not found!', MessageDakama::HTTP_NOT_FOUND);
        }
        $row->restore();
        return MessageDakama::success('Operational hour restored.');
    }
    
}
