<?php

namespace App\Http\Resources\Project;

use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BudgetCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        $data = [];

        foreach ($this as $budget) {
            $data[] = [
                'id'         => $budget->id,
                 'project' => [
                    'id' => $budget->project_id,
                    'name' => optional($budget->project)->name,
                ],
                'nama_budget'  => $budget->nama_budget,
                // 'type'       => $this->typetask($budget),
                 'type' => [
                    'id'    => $budget->type,
                    'type_budget' => $this->typeLabel($budget->type),
                ],
                'nominal'    => $budget->nominal,
                'unit' => $budget->unit,
                'stok' => $budget->stok,
                'created_at' => $budget->created_at,
                'updated_at' => $budget->updated_at,
            ];
        }

        return $data;
    }

    /* protected function typetask(Budget $budget)
    {
        if ($budget->type == Budget::JASA) {
            return 'Jasa';
        } elseif ($budget->type == Budget::MATERIAL) {
            return 'Material';
        } else {
            return 'Tidak Diketahui';
        }
    } */

     protected function typeLabel(int $type): string
    {
        return match ($type) {
            Budget::JASA     => 'Jasa',
            Budget::MATERIAL => 'Material',
            default          => 'Tidak Diketahui',
        };
    }
}
