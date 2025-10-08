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
        $perPage = (int) $request->integer('per_page', 10);

        $query = OperationalHour::query()
            ->with(['projects' => function ($q) {
                $q->select('id', 'name', 'operational_hour_id');
            }])
            ->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $s = $request->string('search');
            $query->where(function ($q) use ($s) {
                $q->where('ontime_start', 'like', "%{$s}%")
                ->orWhere('ontime_end', 'like', "%{$s}%")
                ->orWhere('late_time', 'like', "%{$s}%")
                ->orWhere('offtime', 'like', "%{$s}%");
            });
        }

        $pagination = $query->paginate($perPage);

        // map item (projects -> null kalau kosong)
        $items = collect($pagination->items())->map(function ($row) {
            return [
                'id'           => $row->id,
                'ontime_start' => $row->ontime_start,
                'ontime_end'   => $row->ontime_end,
                'late_time'    => $row->late_time,
                'offtime'      => $row->offtime,
                'deleted_at'   => $row->deleted_at,
                'projects'     => $row->projects->isNotEmpty()
                    ? $row->projects->map(fn ($p) => [
                        'id'   => $p->id,
                        'name' => $p->name,
                    ])->values()
                    : null,
            ];
        })->values();

        // format persis seperti contohmu
        return response()->json([
            'data'  => $items,
            'links' => [
                'first' => $pagination->url(1),
                'last'  => $pagination->url($pagination->lastPage()),
                'prev'  => $pagination->previousPageUrl(),
                'next'  => $pagination->nextPageUrl(),
            ],
            'meta'  => [
                'current_page' => $pagination->currentPage(),
                'from'         => $pagination->firstItem(),
                'last_page'    => $pagination->lastPage(),
                'links'        => $pagination->linkCollection()->toArray(),
                'path'         => $request->url(),
                'per_page'     => $pagination->perPage(),
                'to'           => $pagination->lastItem(),
                'total'        => $pagination->total(),
            ],
        ], 200);
    }


    public function indexall()
    {
        $rows = OperationalHour::query()
            ->with(['projects' => function ($q) {
                $q->select('id', 'name', 'operational_hour_id');
            }])
            ->orderBy('id', 'desc')
            ->get();

        $data = $rows->map(function ($row) {
            return [
                'id'           => $row->id,
                'ontime_start' => $row->ontime_start,
                'ontime_end'   => $row->ontime_end,
                'late_time'    => $row->late_time,
                'offtime'      => $row->offtime,
                'deleted_at'   => $row->deleted_at,
                'projects'     => $row->projects->isNotEmpty()
                    ? $row->projects->map(fn ($p) => [
                        'id'   => $p->id,
                        'name' => $p->name,
                    ])->values()
                : null,
            ];
        })->values();

        return MessageDakama::render([
            'data'        => $data,
        ], MessageDakama::HTTP_OK);
    }

    /** Detail 1 shift by id */
    public function show($id)
    {
        $row = OperationalHour::query()
            ->with(['projects' => function ($q) {
                $q->select('id', 'name', 'operational_hour_id');
            }])
            ->find($id);

        if (!$row) {
            return MessageDakama::warning('Operational hour not found!', MessageDakama::HTTP_NOT_FOUND);
        }

        $data = [
            'id'           => $row->id,
            'ontime_start' => $row->ontime_start,
            'ontime_end'   => $row->ontime_end,
            'late_time'    => $row->late_time,
            'offtime'      => $row->offtime,
            'deleted_at'   => $row->deleted_at,
            'projects'     => $row->projects->isNotEmpty()
                ? $row->projects->map(fn ($p) => [
                    'id'   => $p->id,
                    'name' => $p->name,
                ])->values()
            : null,
        ];

        return MessageDakama::render([
            'status'      => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'data'        => $data,
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
