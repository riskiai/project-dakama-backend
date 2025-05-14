<?php

namespace App\Http\Resources\Project;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Project;
use App\Models\SpbProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SpbProject_Category;
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
                'karyawan' => $project->tenagaKerja() 
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'daily_salary' => $user->salary ? $user->salary->daily_salary : 0,
                        'hourly_salary' => $user->salary ? $user->salary->hourly_salary : 0,
                        'hourly_overtime_salary' => $user->salary ? $user->salary->hourly_overtime_salary : 0,
                        'makan' => $user->salary ? $user->salary->makan : 0,
                        'transport' => $user->salary ? $user->salary->transport : 0,
                        'divisi' => [
                            'id' => optional($user->divisi)->id,
                            'name' => optional($user->divisi)->name,
                        ],
                    ];
                }),
                'tasks' => $project->tasks() 
                ->get()
                ->map(function ($tasks) {
                    return [
                        'id' => $tasks->id,
                        'nama_pekerjaan' => $tasks->nama_task,
                        'type_pekerjaan' => $tasks->type == \App\Models\Task::JASA ? 'Jasa' : 'Material',
                        'nominal' => $tasks->nominal,
                    ];
                }),
                'date' => $project->date,
                'name' => $project->name,
                'billing' => $project->billing,
                'cost_estimate' => $project->cost_estimate,
                'margin' => $project->margin,
                'percent' => $this->formatPercent($project->percent),
                // 'cost_progress_project' => $this->costProgress($project),
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
     * Format percent by removing "%" and rounding the value.
     */
    protected function formatPercent($percent): float
    {
        // Remove "%" if present and convert to float before rounding
        return round(floatval(str_replace('%', '', $percent)), 2);
    }

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

    // protected function costProgress($project)
    // {
    //     $status = Project::STATUS_OPEN;
    //     $totalSpbCost = 0;
    //     $totalManPowerCost = 0;
    //     $totalSpbBoronganCost = 0;

    //     // Ambil SPB projects berdasarkan kondisi kategori
    //     $spbProjects = $project->spbProjects()->get(); // Ambil semua SPB Projects
        
    //     foreach ($spbProjects as $spbProject) {
    //         // Jika kategori adalah Borongan, tampilkan meskipun belum di tab 'paid'
    //         if ($spbProject->spbproject_category_id == SpbProject_Category::BORONGAN) {
    //             $totalSpbBoronganCost += $spbProject->harga_total_pembayaran_borongan_spb ?? 0;
    //         } else {
    //             // Jika kategori bukan Borongan, hanya ambil yang sudah di tab 'paid'
    //             /* if ($spbProject->tab_spb == SpbProject::TAB_PAID) {
    //                 $totalSpbCost += $spbProject->getTotalProdukAttribute();
    //             } */
    //             if (in_array($spbProject->tab_spb, [
    //                 SpbProject::TAB_SUBMIT,
    //                 SpbProject::TAB_VERIFIED,
    //                 SpbProject::TAB_PAYMENT_REQUEST,
    //                 SpbProject::TAB_PAID
    //             ])) {
    //                 $totalSpbCost += $spbProject->getTotalProdukAttribute();
    //             }
    //         }
    //     }

    //     // Hitung total salary dari ManPower terkait proyek
    //     $manPowers = $project->manPowers()->get();
    //     foreach ($manPowers as $manPower) {
    //         $totalManPowerCost += $manPower->current_salary + $manPower->current_overtime_salary;
    //     }

    //     // Total biaya aktual (real cost)
    //     $totalCost = $totalSpbCost + $totalManPowerCost + $totalSpbBoronganCost;

    //     // Percent Itu didapat dari cost estimate project dibagi dengan total cost * 100
    //     if ($project->cost_estimate > 0) {
    //         $costEstimate = round(($totalCost / $project->cost_estimate) * 100, 2);
    //     } else {
    //         $costEstimate = 0;
    //     }

    //     // Tentukan status berdasarkan progres biaya
    //     if ($costEstimate > 90) {
    //         $status = Project::STATUS_NEED_TO_CHECK;
    //     }

    //     if ($costEstimate == 100) {
    //         $status = Project::STATUS_CLOSED;
    //     }

    //     // Update status proyek di database
    //     $project->update(['status_cost_progres' => $status]);

    //     // Kembalikan data progres biaya
    //     return [
    //         'status_cost_progres' => $status,
    //         'percent' => $costEstimate . '%',
    //         'real_cost' => $totalCost,
    //         'spb_produk_cost' => $totalSpbCost,
    //         'spb_borongan_cost' => $totalSpbBoronganCost, 
    //         'man_power_cost' => $totalManPowerCost,
    //     ];
    // }


    // protected function tukangHarianSalary($query) {
    //     return (int) $query->selectRaw("SUM(current_salary + current_overtime_salary) as total")->where("work_type", true)->first()->total;
    // }

    // protected function tukangBoronganSalary($query) {
    //     return (int) $query->selectRaw("SUM(current_salary + current_overtime_salary) as total")->where("work_type", false)->first()->total;
    // }
    

    /* protected function costProgress($project)
    {
        $status = Project::STATUS_OPEN;
        $total = 0;

        $purchases = $project->purchases()->where('tab', Purchase::TAB_PAID)->get();

        foreach ($purchases as $purchase) {
            $total += $purchase->sub_total;
        }

        // Check if cost_estimate is greater than zero before dividing
        if ($project->cost_estimate > 0) {
            $costEstimate = round(($total / $project->cost_estimate) * 100, 2);
        } else {
            // Default value if cost_estimate is zero
            $costEstimate = 0;
        }

        if ($costEstimate > 90) {
            $status = Project::STATUS_NEED_TO_CHECK;
        }

        if ($costEstimate == 100) {
            $status = Project::STATUS_CLOSED;
        }

        // Update the project status in the database
        $project->update(['status_cost_progress' => $status]);

        return [
            'status_cost_progress' => $status,
            'percent' => $costEstimate . '%',
            'real_cost' => $total
        ];
    } */
}
