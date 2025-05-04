<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use Illuminate\Http\Request;
use App\Facades\MessageDakama;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Tax\CreateRequest;
use App\Http\Requests\Tax\UpdateRequest;
use App\Http\Resources\Tax\TaxCollection;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()) {
            // Jika tidak terautentikasi, kirimkan response error dengan status 401 Unauthorized
            return MessageDakama::errorauth("You must be logged in to access this resource.");
        }

        $query = Tax::query();

        if ($request->has('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('id', 'like', '%' . $request->search . '%');
                $query->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('date')) {
            $date = str_replace(['[', ']'], '', $request->date);
            $date = explode(", ", $date);

            $query->whereBetween('created_at', $date);
        }

        $tax = $query->paginate($request->per_page);

        return new TaxCollection($tax);
    }

    public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        try {
            $tax = Tax::create($request->all());

            DB::commit();
            return MessageDakama::success("tax $tax->name has been created");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function show(string $id)
    {
        $tax = Tax::find($id);
        if (!$tax) {
            return MessageDakama::notFound('data not found!');
        }

        return MessageDakama::render([
            'status' => MessageDakama::SUCCESS,
            'status_code' => MessageDakama::HTTP_OK,
            'data' => [
                'id' => $tax->id,
                'name' => $tax->name,
                'description' => $tax->description,
                'percent' => $tax->percent,
                'type' => $tax->type,
                'created_at' => $tax->created_at,
                'updated_at' => $tax->updated_at,
            ]
        ]);
    }

    public function update(UpdateRequest $request, $id)
    {
        DB::beginTransaction();

        $tax = Tax::find($id);
        if (!$tax) {
            return MessageDakama::notFound('data not found!');
        }

        try {
            $tax->update($request->all());

            DB::commit();
            return MessageDakama::success("tax $tax->name has been updated");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();

        $tax = Tax::find($id);
        if (!$tax) {
            return MessageDakama::notFound('data not found!');
        }

        try {
            $tax->delete();

            DB::commit();
            return MessageDakama::success("tax $tax->name has been deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }
}
