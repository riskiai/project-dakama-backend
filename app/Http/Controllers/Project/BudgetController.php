<?php

namespace App\Http\Controllers\Project;

use App\Models\Budget;
use Illuminate\Http\Request;
use App\Facades\MessageDakama;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\Project\BudgetCollection;
use App\Http\Requests\Project\Budget\CreateRequest;
use App\Http\Requests\Project\Budget\UpdateRequest;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $query = Budget::query();

        // Optional: tambahkan filter jika ada request type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $budgets = $query->paginate($request->per_page);

        return new BudgetCollection($budgets);
    }

     public function indexall(Request $request)
    {
        $query = Budget::query();

        // Optional: tambahkan filter jika ada request type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $budgets = $query->get();

        return new BudgetCollection($budgets);
    }

    public function store(CreateRequest $request)
    {
        DB::beginTransaction();

        try {
            $budget = Budget::create([
                'project_id'   => $request->project_id,
                'nama_budget'  => $request->nama_budget,
                'type'         => $request->type,
                'nominal'      => $request->nominal,
            ]);

            DB::commit();
            return MessageDakama::success("Budget '{$budget->nama_budget}' berhasil dibuat.");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal membuat budget: " . $th->getMessage());
        }
    }

     public function show($id)
    {
        $budget = Budget::with('project')->find($id);

        if (!$budget) {
            return MessageDakama::notFound("Data budget dengan ID $id tidak ditemukan.");
        }

        return response()->json([
            'id' => $budget->id,
            'project' => [
                'id' => $budget->project_id,
                'name' => optional($budget->project)->name,
            ],
            'nama_budget' => $budget->nama_budget,
            'type' => $budget->type == Budget::JASA ? 'Jasa' : 'Material',
            'nominal' => (float) $budget->nominal,
            'created_at' => $budget->created_at,
            'updated_at' => $budget->updated_at,
        ]);
    }

    public function update(UpdateRequest $request, $id)
    {
        DB::beginTransaction();

        $budget = Budget::find($id);
        if (!$budget) {
            return MessageDakama::notFound("Data budget dengan ID $id tidak ditemukan.");
        }

        try {
            $budget->update([
                'project_id'   => $request->project_id,
                'nama_budget'  => $request->nama_budget,
                'type'         => $request->type,
                'nominal'      => $request->nominal,
            ]);

            DB::commit();
            return MessageDakama::success("Budget '{$budget->nama_budget}' berhasil diupdate.");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error("Gagal mengupdate budget: " . $th->getMessage());
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();

        $budget = Budget::find($id);
        if (!$budget) {
            return MessageDakama::notFound('data not found!');
        }

        try {
            $budget->delete();

            DB::commit();
            return MessageDakama::success("budget $budget->name has been deleted");
        } catch (\Throwable $th) {
            DB::rollBack();
            return MessageDakama::error($th->getMessage());
        }
    }

}
