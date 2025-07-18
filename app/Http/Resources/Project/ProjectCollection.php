<?php

namespace App\Http\Resources\Project;

use Carbon\Carbon;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProjectCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */

     
    public function toArray(Request $request): array
    {
        $user = auth()->user();
        $role = $user->role_id;
        $data = [];

        foreach ($this as $key => $project) {   
            
            
            $data[] = [
                'id' => $project->id,
                'no_dokumen_project' => $project->no_dokumen_project,
                'client' => [
                    'id' => optional($project->company)->id,
                    'name' => optional($project->company)->name,
                    'contact_type' => optional($project->company)->contactType?->name,
                ],
               'tasks' => $project
                    ->tasksDirect()                     // ← pakai ()
                    ->select('tasks.*')                 // samakan kolom
                    ->union(
                        $project->tasks()->select('tasks.*')   // relasi pivot
                    )
                    ->orderByDesc('created_at')         // sekarang aman: query builder
                    ->get()
                    ->map(function ($task) {
                        return [
                            'id'             => $task->id,
                            'nama_pekerjaan' => $task->nama_task,
                            'type_pekerjaan' => $task->type == \App\Models\Task::JASA ? 'Jasa' : 'Material',
                            'nominal'        => $task->nominal,
                        ];
                    }),

                'budgets' => $project
                        ->budgetsDirect()                // gunakan query builder
                        ->orderByDesc('created_at')      // terbaru dahulu
                        ->get()
                        ->map(function ($budget) {
                            return [
                                'id'            => $budget->id,
                                'nama_budget'   => $budget->nama_budget,
                                'type_budget'   => $budget->type == \App\Models\Budget::JASA ? 'Jasa' : 'Material',
                                'nominal'       => $budget->nominal,
                                'unit'          => $budget->unit,
                                'stok'          => $budget->stok,
                            ];
                }),
                'billing' => $project->billing,
                'margin'  => $this->formatMargin($project),
                'percent' => $this->formatPercent(
                                $project,
                                $this->formatMargin($project)   
                            ),
                'date' => $project->date,
                'name' => $project->name,
                // 'percent' => $this->formatPercent($project->percent),
                // 'margin' => $project->margin,
                // 'cost_estimate' => $project->cost_estimate,
                'cost_progress_project' => $this->costProgress($project),
                'file_attachment' => [
                    'name' => $project->file ? date('Y', strtotime($project->created_at)) . '/' . $project->id . '.' . pathinfo($project->file, PATHINFO_EXTENSION) : null,
                    'link' => $project->file ? asset("storage/$project->file") : null,
                ],
                // 'status_step_project' => $this->getStepStatus($project->status_step_project),
                'request_status_owner' => $this->getRequestStatus($project->request_status_owner),
                'status_bonus_project' => $this->getRequestStatusBonus($project->status_bonus_project),
                'type_projects' => $this->getDataTypeProject($project->type_projects),
                'sisa_pembayaran_termin' => $this->getDataSisaPemabayaranTerminProyek($project),
                "harga_total_termin_proyek" => $this->getHargaTerminProyek($project),
                "deskripsi_termin_proyek" => $this->getDeskripsiTerminProyek($project),
                "type_termin_proyek" => $this->convertTypeTermin($this->decodeJson($project->type_termin_proyek)),
                "payment_date_termin_proyek" => $project->latest_payment_date, 
                "file_payment_termin_proyek" => $project->file_pembayaran_termin ? asset("storage/{$project->file_pembayaran_termin}") : null,
                "file_payment_termin_proyek" => $this->getLatestPaymentFile($project), 
                "riwayat_termin" => $this->getRiwayatTermin($project),
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at,
            ];

            if ($project->user) {
                $data[$key]['created_by'] = [
                    "id" => $project->user->id,
                    "name" => $project->user->name,
                    "created_at" => Carbon::parse($project->created_at)->timezone('Asia/Jakarta')->toDateTimeString(),
                ];
            }
            
            if ($project->user) {
                $data[$key]['updated_by'] = [
                    "id" => $project->user->id,
                    "name" => $project->user->name,
                    "updated_at" => Carbon::parse($project->updated_at)->timezone('Asia/Jakarta')->toDateTimeString(),
                ];
            }
        }

        return $data;
    }

    
    /**
     * Hitung margin proyek.
     * - Jika belum ada budget (sum = 0) kembalikan 0.
     */
    protected function formatMargin(Project $project): float
    {
        $totalBudget = (float) $project->budgetsDirect()->sum('nominal');

        // Belum ada biaya => margin 0
        if ($totalBudget <= 0) {
            return 0.0;
        }

        $billing = (float) $project->billing;
        return round($billing - $totalBudget, 2);
    }

    /**
     * Hitung persentase profit margin.
     * - Jika margin = 0 atau billing = 0, hasil 0 %.
     */
    protected function formatPercent(Project $project, float $margin): float
    {
        $billing = (float) $project->billing;

        if ($billing <= 0 || $margin <= 0) {
            return 0.0;
        }

        return round(($margin / $billing) * 100, 2);
    }



    /* protected function formatPercent($percent): float
    {
        return round(floatval(str_replace('%', '', $percent)), 2);
    } */

    protected function getDataTypeProject($status) {
        $statuses = [

            Project::HIK => "HIK PROJECT",
            Project::DWI => "DWI PROJECT",
         ];

        return [
            "id" => $status,
            "name" => $statuses[$status] ?? "Unknown",
        ];
    }

    protected function getRequestStatusBonus($status) {
        $statuses = [

            Project::BELUM_DIKASIH_BONUS => "Belum Dikasih Bonus",
            Project::SUDAH_DIKASIH_BONUS => "Sudah Dikasih Bonus",
         ];

        return [
            "id" => $status,
            "name" => $statuses[$status] ?? "Unknown",
        ];
    }

    protected function getRequestStatus($status)
    {
        $statuses = [
            Project::PENDING => "Pending",
            Project::ACTIVE => "Active",
            Project::REJECTED => "Rejected",
            Project::CLOSED => "Closed",
            Project::CANCEL => "Cancel",
        ];

        return [
            "id" => $status,
            "name" => $statuses[$status] ?? "Unknown",
        ];
    }

    /* Termin Proyek */
     protected function getDataSisaPemabayaranTerminProyek(Project $project)
    {
        // Ambil total billing proyek
        $billing = $project->billing;
    
        // Hitung total harga termin yang sudah dibayar (total harga termin yang ada di proyek)
        $totalHargaTermin = $this->getHargaTerminProyek($project);
    
        // Jika total harga termin adalah 0, berarti belum ada pembayaran, maka kembalikan 0
        if ($totalHargaTermin == 0) {
            return 0;
        }
    
        // Sisa pembayaran = Billing - Total harga termin
        $sisaPembayaran = $billing - $totalHargaTermin;
    
        return $sisaPembayaran; // Mengembalikan sisa pembayaran
    }
    

    protected function getLatestPaymentFile(Project $project)
    {
        // Ambil termin terbaru yang memiliki bukti pembayaran
        $terminWithFile = $project->projectTermins()
            ->whereNotNull('file_attachment_pembayaran')
            ->orderBy('tanggal_payment', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        return $terminWithFile ? asset("storage/{$terminWithFile->file_attachment_pembayaran}") : null;
    }

    protected function getHargaTerminProyek(Project $project)
    {
        return $project->projectTermins->sum('harga_termin') ?? null;
    }

    protected function getDeskripsiTerminProyek(Project $project)
    {
        return $project->deskripsi_termin_proyek ?? null;
    }

    protected function getRiwayatTermin(Project $project)
    {
        return $project->projectTermins->map(function ($termin) {
            return [
                'id' => $termin->id,
                'harga_termin' => $termin->harga_termin,
                'deskripsi_termin' => $termin->deskripsi_termin,
                'type_termin_spb' => $this->convertTypeTermin($this->decodeJson($termin->type_termin)),
                'tanggal' => $termin->tanggal_payment,
                'file_attachment' => $termin->file_attachment_pembayaran
                    ? [
                        'name' => pathinfo($termin->file_attachment_pembayaran, PATHINFO_FILENAME),
                        'link' => asset("storage/{$termin->file_attachment_pembayaran}"),
                    ]
                    : null,
            ];
        })->toArray();
    }

    protected function convertTypeTermin($status)
    {
        if (is_array($status)) {
            $id = $status['id'] ?? null;
        } else {
            $id = (string) $status;
        }

        return [
            "id" => $id,
            "name" => is_null($id) ? "Unknown" : ($id == Project::TYPE_TERMIN_PROYEK_LUNAS ? "Lunas" : "Belum Lunas"),
        ];
    }

    protected function decodeJson($json)
    {
        if (is_array($json)) {
            return $json;
        }
    
        $decoded = json_decode($json, true);
    
        return $decoded ?? ["id" => null, "name" => "Unknown"];
    }

    protected function costProgress(Project $project): array
    {
        /* ───────── 1. TOTAL PURCHASE ───────── */
        $totalPurchaseCost = $project->purchases()
            ->whereIn('tab', [
                Purchase::TAB_SUBMIT,
                Purchase::TAB_VERIFIED,
                Purchase::TAB_PAYMENT_REQUEST,
                Purchase::TAB_PAID,
            ])
            ->get()                     // ← ambil sebagai Collection
            ->sum('net_total');         // ← accessor dihitung di PHP, bukan SQL

        /* ───────── 2. TOTAL MAN-POWER ──────── */
        $att = Attendance::where('project_id', $project->id)
            ->where('status', Attendance::ATTENDANCE_OUT)
            ->selectRaw('
                SUM(daily_salary)           AS daily,
                SUM(hourly_overtime_salary) AS overtime,
                SUM(late_cut)               AS late_cut
            ')
            ->first();

        $totalPayrollCost = ($att->daily ?? 0)
                        + ($att->overtime ?? 0)
                        - ($att->late_cut ?? 0);

        /* ───────── 3. REAL COST & STATUS ───── */
        $realCost = $totalPurchaseCost + $totalPayrollCost;

        $percent = $project->cost_estimate > 0
            ? round(($realCost / $project->cost_estimate) * 100, 2)
            : 0;

        $status = Project::STATUS_OPEN;
        if ($percent > 90 && $percent < 100) {
            $status = Project::STATUS_NEED_TO_CHECK;
        } elseif ($percent >= 100) {
            $status = Project::STATUS_CLOSED;
        }

        $project->update(['status_cost_progres' => $status]);

        /* ───────── 4. RESPON ───────── */
        return [
            'status_cost_progres' => $status,
            'percent'             => $percent . '%',
            'real_cost'           => $realCost,
            'purchase_cost'       => $totalPurchaseCost,
            'payroll_cost'        => $totalPayrollCost,
        ];
    }


    // protected function tukangHarianSalary($query) {
    //     return (int) $query->selectRaw("SUM(current_salary + current_overtime_salary) as total")->where("work_type", true)->first()->total;
    // }

    // protected function tukangBoronganSalary($query) {
    //     return (int) $query->selectRaw("SUM(current_salary + current_overtime_salary) as total")->where("work_type", false)->first()->total;
    // }
    
}
