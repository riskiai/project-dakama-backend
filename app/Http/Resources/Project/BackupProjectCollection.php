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
                
            $totalUnapprovedSpb = 0;

            // Hitung jumlah SPB yang belum disetujui berdasarkan role yang relevan, dengan mengecualikan kategori Flash Cash
            /* switch ($role) {
                case Role::GUDANG:
                    $totalUnapprovedSpb = $project->spbProjects()
                        ->whereHas('category', function ($q) {
                            $q->whereNotIn('spbproject_category_id', [
                                SpbProject_Category::FLASH_CASH,
                                SpbProject_Category::BORONGAN
                            ]);
                        })
                        ->whereNull('know_kepalagudang') // Belum disetujui oleh GUDANG
                        ->count();
                    break;
            
                case Role::SUPERVISOR:
                    $totalUnapprovedSpb = $project->spbProjects()
                        ->whereHas('category', function ($q) {
                            $q->whereNotIn('spbproject_category_id', [
                                SpbProject_Category::FLASH_CASH,
                                SpbProject_Category::BORONGAN
                            ]);
                        })
                        ->whereNull('know_supervisor') // Belum disetujui oleh SUPERVISOR
                        ->count();
                    break;
            
                case Role::OWNER:
                    $totalUnapprovedSpb = $project->spbProjects()
                        ->whereHas('category', function ($q) {
                            $q->where('spbproject_category_id', '!=', SpbProject_Category::FLASH_CASH);
                        })
                        ->whereNull('request_owner') // Belum disetujui oleh OWNER
                        ->count();
                    break;
            
                default:
                    // Jika role tidak dikenali, tidak ada data SPB yang dihitung
                    break;
            } */
            
            
            $data[] = [
                'id' => $project->id,
                'no_dokumen_project' => $project->no_dokumen_project,
                'client' => [
                    'id' => optional($project->company)->id,
                    'name' => optional($project->company)->name,
                    'contact_type' => optional($project->company)->contactType?->name,
                ],
                /* 'produk' => optional($project->product)->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'nama' => $product->nama,
                            'deskripsi' => $product->deskripsi,
                            'stok' => $product->stok,
                            'harga' => $product->harga,
                            'type_pembelian' => $product->type_pembelian,
                            'kode_produk' => $product->kode_produk,
                        ];
                    }), */
                'marketing' => $project->tenagaKerja()
                    ->whereHas('role', function ($query) {
                        $query->where('role_name', 'Marketing');
                    })
                    ->first() // Mengambil hanya satu data
                    ?->loadMissing(['salary', 'divisi']) // Memastikan salary dan divisi dimuat
                    ? [
                        'id' => $project->tenagaKerja()
                            ->whereHas('role', function ($query) {
                                $query->where('role_name', 'Marketing');
                            })
                            ->first()?->id ?? null,
                        'name' => $project->tenagaKerja()
                            ->whereHas('role', function ($query) {
                                $query->where('role_name', 'Marketing');
                            })
                            ->first()?->name ?? null,
                        'daily_salary' => $project->tenagaKerja()
                            ->whereHas('role', function ($query) {
                                $query->where('role_name', 'Marketing');
                            })
                            ->first()?->salary->daily_salary ?? 0,
                        'hourly_salary' => $project->tenagaKerja()
                            ->whereHas('role', function ($query) {
                                $query->where('role_name', 'Marketing');
                            })
                            ->first()?->salary->hourly_salary ?? 0,
                        'hourly_overtime_salary' => $project->tenagaKerja()
                            ->whereHas('role', function ($query) {
                                $query->where('role_name', 'Marketing');
                            })
                            ->first()?->salary->hourly_overtime_salary ?? 0,
                        'divisi' => [
                            'id' => $project->tenagaKerja()
                                ->whereHas('role', function ($query) {
                                    $query->where('role_name', 'Marketing');
                                })
                                ->first()?->divisi->id ?? null,
                            'name' => $project->tenagaKerja()
                                ->whereHas('role', function ($query) {
                                    $query->where('role_name', 'Marketing');
                                })
                                ->first()?->divisi->name ?? null,
                        ],
                    ]
                    : null,
                'supervisor' => $project->tenagaKerja()
                    ->whereHas('role', function ($query) {
                        $query->where('role_name', 'Supervisor');
                    })
                    ->first() // Mengambil hanya satu data
                    ?->loadMissing(['salary', 'divisi']) // Memastikan salary dan divisi dimuat
                    ? [
                        'id' => $project->tenagaKerja()
                            ->whereHas('role', function ($query) {
                                $query->where('role_name', 'Supervisor');
                            })
                            ->first()?->id ?? null,
                        'name' => $project->tenagaKerja()
                            ->whereHas('role', function ($query) {
                                $query->where('role_name', 'Supervisor');
                            })
                            ->first()?->name ?? null,
                        'daily_salary' => $project->tenagaKerja()
                            ->whereHas('role', function ($query) {
                                $query->where('role_name', 'Supervisor');
                            })
                            ->first()?->salary->daily_salary ?? 0,
                        'hourly_salary' => $project->tenagaKerja()
                            ->whereHas('role', function ($query) {
                                $query->where('role_name', 'Supervisor');
                            })
                            ->first()?->salary->hourly_salary ?? 0,
                        'hourly_overtime_salary' => $project->tenagaKerja()
                            ->whereHas('role', function ($query) {
                                $query->where('role_name', 'Supervisor');
                            })
                            ->first()?->salary->hourly_overtime_salary ?? 0,
                        'divisi' => [
                            'id' => $project->tenagaKerja()
                                ->whereHas('role', function ($query) {
                                    $query->where('role_name', 'Supervisor');
                                })
                                ->first()?->divisi->id ?? null,
                            'name' => $project->tenagaKerja()
                                ->whereHas('role', function ($query) {
                                    $query->where('role_name', 'Supervisor');
                                })
                                ->first()?->divisi->name ?? null,
                        ],
                    ]
                    : null,
                'tukang' => $project->tenagaKerja() 
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'daily_salary' => $user->salary ? $user->salary->daily_salary : 0,
                        'hourly_salary' => $user->salary ? $user->salary->hourly_salary : 0,
                        'hourly_overtime_salary' => $user->salary ? $user->salary->hourly_overtime_salary : 0,
                        'divisi' => [
                            'id' => optional($user->divisi)->id,
                            'name' => optional($user->divisi)->name,
                        ],
                    ];
                }),
              /*   'summary_salary_manpower' => [
                    'tukang_harian' => $this->tukangHarianSalary($project->manPowers()),
                    'tukang_borongan' => $this->tukangBoronganSalary($project->manPowers()),
                    'total' => $this->tukangHarianSalary($project->manPowers()) + $this->tukangBoronganSalary($project->manPowers()),
                ], */
                // 'total_spb_unapproved_for_role' => $totalUnapprovedSpb,
                // Menampilkan seluruh produk yang terkait tanpa memfilter berdasarkan status PAID
               /*  'spb_projects' => $project->spbProjects->map(function ($spbProject) {
                    $spbCategory = $spbProject->spbproject_category_id;
                    $isBorongan = $spbCategory == \App\Models\SpbProject_Category::BORONGAN;

                    return [
                        'doc_no_spb' => $spbProject->doc_no_spb,
                        'doc_type_spb' => $spbProject->doc_type_spb,
                        'unit_kerja' => $spbProject->unit_kerja,
                        'tanggal_dibuat_spb' => $spbProject->tanggal_dibuat_spb,
                        'tanggal_berahir_spb' => $spbProject->tanggal_berahir_spb,
                        
                        // Menambahkan informasi kategori Borongan
                        'kategori_spb' => \App\Models\SpbProject_Category::getCategoryName($spbProject->spbproject_category_id),
                        
                        // Menampilkan produk yang terkait
                        'produk' => $spbProject->productCompanySpbprojects->map(function ($product) use ($spbProject) {
                            return [
                                'produk_id' => $product->produk_id,
                                'produk_nama' => $product->product->nama ?? 'Unknown',
                                'harga_product' => $product->product ? $product->product->harga_product : 'Unknown',
                                'vendor_id' => $product->company->id ?? 'Unknown',
                                'vendor_name' => $product->company->name ?? 'Unknown',
                                'subtotal_produk' => $product->subtotal_produk,
                            ];
                        }),
                        'total_keseluruhanproduk' => $spbProject->total_produk,

                        // Menambahkan kondisi untuk biaya borongan
                        'spb_borongan_cost' => $isBorongan ? $spbProject->harga_total_pembayaran_borongan_spb : null, // Menampilkan harga borongan jika kategori borongan
                    ];
                }), */
                'date' => $project->date,
                'name' => $project->name,
                'billing' => $project->billing,
                'cost_estimate' => $project->cost_estimate,
                'margin' => $project->margin,
                'percent' => $this->formatPercent($project->percent),
                // 'cost_progress_project' => $this->costProgress($project),
                'harga_type_project' => $project->harga_type_project ?? 0,
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
                // "file_payment_termin_proyek" => $project->file_pembayaran_termin ? asset("storage/{$project->file_pembayaran_termin}") : null,
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
    
        $sisaPembayaran = $billing - $totalHargaTermin;
    
        return $sisaPembayaran; // Mengembalikan sisa pembayaran
    }
    

    protected function getLatestPaymentFile(Project $project)
    {
        $terminWithFile = $project->projectTermins()
            ->whereNotNull('file_attachment_pembayaran')
            ->orderBy('tanggal_payment', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        return $terminWithFile ? asset("storage/{$terminWithFile->file_attachment_pembayaran}") : null;
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

    /* 
        protected function getHargaTerminProyek(Project $project)
        {
            return $project->projectTermins->sum('harga_termin') ?? null;
        } 
    */

    /*  
        protected function getDeskripsiTerminProyek(Project $project)
        {
            return $project->deskripsi_termin_proyek ?? null;
        } 
    */

   /*  protected function getRiwayatTermin(Project $project)
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
   */

   /*  protected function convertTypeTermin($status)
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
    */

   /*  protected function decodeJson($json)
    {
        if (is_array($json)) {
            return $json;
        }
    
        $decoded = json_decode($json, true);
    
        return $decoded ?? ["id" => null, "name" => "Unknown"];
    } 
   */
    

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
