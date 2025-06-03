<?php

namespace App\Http\Resources\Purchase;

use App\Models\Purchase;
use App\Models\PurchaseStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [];

        foreach ($this as $key => $purchase) {
             $tabId = $this->getTabLabel($purchase);
             $subTotal   = (float) $purchase->sub_total_purchase;

              $rejectLog = $purchase->logs
                ->whereNotNull('note_reject')          // hanya log yg ada catatan reject
                ->sortByDesc('created_at')             // terbaru dulu
                ->first();

                $notification = null;

                if (
                    $purchase->purchase_status_id == PurchaseStatus::REJECTED &&     // status sekarang
                    ($rejectLog = $purchase->logs
                        ->whereNotNull('note_reject')
                        ->sortByDesc('created_at')
                        ->first())
                ) {
                    $notification = "{$purchase->doc_no} ditolak oleh {$rejectLog->name}";
                }

            /* ===== hitung PPH ===== */
            $pphRatePercent = 0;
            $pphAmount      = 0;

            if ($purchase->pph) {
                // relasi belum ada? pakai query biasa
                $tax = \App\Models\Tax::find($purchase->pph);

                if ($tax) {
                    $pphRatePercent = (float) $tax->percent;               // ex: 2  → 2%
                    $rateDecimal    = $pphRatePercent > 1
                                        ? $pphRatePercent / 100            // 2 → 0.02
                                        : $pphRatePercent;                 // already decimal
                    $pphAmount      = round($subTotal * $rateDecimal, 2);
                }
            }

            $data[$key] = [
                "doc_no" => $purchase->doc_no,
                "doc_type" => $purchase->doc_type,
                "purchase_type" => $purchase->purchase_id == Purchase::TYPE_EVENT ? Purchase::TEXT_EVENT : Purchase::TEXT_OPERATIONAL,
                /* "vendor_name" => [
                    "id" => $purchase->company->id,
                    "name" => $purchase->company->name,
                    "bank" => $purchase->company->bank_name,
                    "account_name" => $purchase->company->account_name,
                    "account_number" => $purchase->company->account_number,
                ], */
                 'tab_purchase' => [
                    'id'   => $tabId,
                    'name' => Purchase::TAB_LABELS[$tabId] ?? 'Unknown',
                ],
                "status_purchase" => $this->getStatus($purchase),
                /* 'logs_purchase' => $purchase->logs
                    ->sortByDesc('created_at')
                    ->map(function ($log) use ($purchase) {
                        return [
                            'tab'   => [
                                'id'   => $log->tab,
                                'name' => \App\Models\Purchase::TAB_LABELS[$log->tab] ?? 'Unknown',
                            ],
                            'status' => [
                                'id'   => $this->calculateStatusId($purchase, $log),
                                'name' => $this->calculateStatusText($purchase, $log),
                            ],
                            'name'        => $log->name,
                            'note_reject' => $log->note_reject,
                            'is_rejected' => $log->note_reject !== null,
                            // 'created_at'  => $log->created_at->format('Y-m-d H:i:s'),
                        ];
                    })
                    ->values()
                    ->toArray(), 
                */
                'rejected_notification' => $notification, 
                /* ---------- LOG TERBARU ---------- */
                'log_purchase' => (function () use ($purchase) {
                    $log = $purchase->logs->sortByDesc('created_at')->first();   // ambil satu log terbaru

                    if (!$log) {
                        return null; // tidak ada log sama sekali
                    }

                    return [
                        'tab' => [
                            'id'   => $log->tab,
                            'name' => Purchase::TAB_LABELS[$log->tab] ?? 'Unknown',
                        ],
                        'status' => [
                            'id'   => $this->calculateStatusId($purchase, $log),
                            'name' => $this->calculateStatusText($purchase, $log),
                        ],
                        'name'        => $log->name,
                        'note_reject' => $log->note_reject,
                        'is_rejected' => $log->note_reject !== null,
                        'created_at'  => $log->created_at->format('Y-m-d H:i:s'),
                    ];
                })(),

                "description" => $purchase->description,
                "remarks" => $purchase->remarks,
                // "file_bukti_pembelian_product_purchases" => $this->getDocument($purchase),
                'file_bukti_pembelian_product_purchases' => $this->getDocument($purchase),
                "date_start_create_purchase" => $purchase->date,
                "due_date_end_purchase" => $purchase->due_date,
                "project" => $purchase->project
                    ? [
                        "id"   => $purchase->project->id,
                        "name" => $purchase->project->name,
                      ]
                    : null,

                 "products" => $purchase->productCompanies->map(function ($prod) {

                    /* hitung dasar (harga × stok) */
                    $base  = $prod->harga * $prod->stok;
                    /* konversi rate: 11  → 0.11 ;  null → 0 */
                    $rate  = $prod->ppn ? ((float) $prod->ppn > 1 ? (float) $prod->ppn / 100 : (float) $prod->ppn) : 0;
                    /* rupiah PPN */
                    $ppnAmount = round($base * $rate, 2);

                    return [
                        'id'                     => $prod->id,
                        'vendor'    => [
                            'id'   => $prod->company_id,
                            'name' => $prod->company?->name,
                        ],
                        'product_name'           => $prod->product_name,
                        'harga'                  => $prod->harga,
                        'stok'                   => $prod->stok,
                        'subtotal_harga_product' => $prod->subtotal_harga_product,

                        /* ⇢ blok baru */
                        'ppn' => [
                            'rate'   => $prod->ppn ? (float) $prod->ppn : 0,  
                            'amount' => $ppnAmount,                          
                        ],
                    ];
                }),
                'file_bukti_pembayaran_product_purchases' => $this->getDocumentPembayaran($purchase),
                'tanggal_pembayaran_purchase' => $purchase->tanggal_pembayaran_purchase,
                'sub_total_purchase'     => (float) $purchase->sub_total_purchase,
                'pph' => [
                    'rate'   => $pphRatePercent,   // ex: 2 (%)
                    'amount' => $pphAmount,        // rupiah PPh
                ],
                'total' => $subTotal - $pphAmount,
                // "logs_rejected" => $purchase->logs()->select('name', 'note_reject', 'created_at')->where('note_reject', '!=', null)->orderBy('id', 'desc')->get(),
                "created_at" => $purchase->created_at->format('Y-m-d'),
                "updated_at" => $purchase->updated_at->format('Y-m-d'),                
                
            ];

            if ($purchase->user) {
                $data[$key]['created_by'] = [
                    "id" => $purchase->user->id,
                    "name" => $purchase->user->name,
                ];
            }

           /*  if ($purchase->purchase_id == Purchase::TYPE_EVENT) {
                if ($purchase->project) {
                    $data[$key]['project'] = [
                        "id" => $purchase->project->id,
                        "name" => $purchase->project->name,
                    ];
                }
            } */

            /* if ($purchase->pph) {
                $data[$key]['pph'] = $this->getPph($purchase);
             } 
            */
        }

        return $data;
    }

    protected function calculateStatusId($purchase, $log)
    {
        /* 1) Jika log menyimpan status eksplisit (Paid / Rejected / Awaiting dll.) */
        if ($log && $log->purchase_status_id) {
            return $log->purchase_status_id;
        }

        /* 2) Pada tab Submit gunakan status di header (Awaiting / Rejected) */
        if ($purchase->tab == Purchase::TAB_SUBMIT) {
            return $purchase->purchase_status_id;
        }

        /* 3) Pada tab Verified / Payment-Request hitung OPEN | DUEDATE | OVERDUE */
        $due = $purchase->due_date ? Carbon::parse($purchase->due_date) : null;
        $now = Carbon::today();

        if (!$due)                    return PurchaseStatus::OPEN;
        if ($now->gt($due))           return PurchaseStatus::OVERDUE;
        if ($now->eq($due))           return PurchaseStatus::DUEDATE;

        return PurchaseStatus::OPEN;
    }

    /* ------------------------------------------------------------------
    *  HELPER – ubah ID → teks
    * ------------------------------------------------------------------ */
    protected function calculateStatusText($purchase, $log)
    {
        return match ($this->calculateStatusId($purchase, $log)) {
            PurchaseStatus::PAID      => PurchaseStatus::TEXT_PAID,
            PurchaseStatus::REJECTED  => PurchaseStatus::TEXT_REJECTED,
            PurchaseStatus::AWAITING  => PurchaseStatus::TEXT_AWAITING,   // ← tambahkan
            PurchaseStatus::OVERDUE   => PurchaseStatus::TEXT_OVERDUE,
            PurchaseStatus::DUEDATE   => PurchaseStatus::TEXT_DUEDATE,
            default                   => PurchaseStatus::TEXT_OPEN,
        };
    }


    protected function getDocumentPembayaran($purchase)
    {
        return $purchase->documents
            ->where('type_file', \App\Models\Document::BUKTI_PEMBAYARAN)   // ambil yang tipe bukti pembayaran
            ->map(function ($doc) {
                return [
                    'id'        => $doc->id,
                    'name'      => "{$doc->purchase->doc_type}/{$doc->doc_no}.{$doc->id}/" .
                                date('Y', strtotime($doc->created_at)) . '.' .
                                pathinfo($doc->file_path, PATHINFO_EXTENSION),
                    'link'      => asset("storage/{$doc->file_path}"),
                    'type_file' => $doc->type_file,
                ];
            })
            ->values()
            ->toArray();
    }


   protected function getDocument($purchase)
    {
        return $purchase->documents
            ->where('type_file', \App\Models\Document::BUKTI_PEMBELIAN)   // sesuaikan field-nya
            ->map(function ($doc) {
                return [
                    'id'        => $doc->id,
                    'name'      => "{$doc->purchase->doc_type}/{$doc->doc_no}.{$doc->id}/" .
                                date('Y', strtotime($doc->created_at)) . '.' .
                                pathinfo($doc->file_path, PATHINFO_EXTENSION),
                    'link'      => asset("storage/{$doc->file_path}"),
                    'type_file' => $doc->type_file,  // isinya: 1 (BUKTI_PEMBELIAN) atau 2 (BUKTI_PEMBAYARAN)
                ];
            })
            ->values()
            ->toArray();
    }

    protected function getTabLabel($purchase): int
    {
        switch ($purchase->purchase_status_id) {

            // 1) menunggu verifikasi
            case PurchaseStatus::AWAITING:          // status “Awaiting”
            case PurchaseStatus::REJECTED:          // (jika masih di-submit & pernah ditolak)
                return Purchase::TAB_SUBMIT;        // ⇒ tab Submit

            // 2) sudah LUNAS
            case PurchaseStatus::PAID:
                return Purchase::TAB_PAID;          // ⇒ tab Paid

            // 3) status OPEN / DUEDATE / OVERDUE
            default:
                // jika purchase sudah masuk tahap minta pembayaran,
                // tetap gunakan tab Payment Request (3) ­—
                // selain itu anggap masih di tahap Verified (2)
                return $purchase->tab == Purchase::TAB_PAYMENT_REQUEST
                    ? Purchase::TAB_PAYMENT_REQUEST
                    : Purchase::TAB_VERIFIED;
        }
    }


    protected function getStatus($purchase)
    {
        $data = [];

        if ($purchase->tab == Purchase::TAB_SUBMIT) {
            $data = [
                "id" => $purchase->purchaseStatus->id,
                "name" => $purchase->purchaseStatus->name,
            ];

            if ($purchase->purchase_status_id == PurchaseStatus::REJECTED) {
                $data['name'] = PurchaseStatus::TEXT_REJECTED; // ← override label
                $data['note'] = $purchase->reject_note;
            }

        }

        if ($purchase->tab == Purchase::TAB_PAID) {
            $data = [
                "id" => PurchaseStatus::PAID,
                "name" => PurchaseStatus::TEXT_PAID,
            ];
        }


        if (
            $purchase->tab == Purchase::TAB_VERIFIED ||
            $purchase->tab == Purchase::TAB_PAYMENT_REQUEST
        ) {
            $dueDate = Carbon::createFromFormat("Y-m-d", $purchase->due_date);
            $nowDate = Carbon::now();

            $data = [
                "id" => PurchaseStatus::OPEN,
                "name" => PurchaseStatus::TEXT_OPEN,
            ];

            if ($nowDate->gt($dueDate)) {
                $data = [
                    "id" => PurchaseStatus::OVERDUE,
                    "name" => PurchaseStatus::TEXT_OVERDUE,
                ];
            }

            if ($nowDate->toDateString() == $purchase->due_date) {
                $data = [
                    "id" => PurchaseStatus::DUEDATE,
                    "name" => PurchaseStatus::TEXT_DUEDATE,
                ];
            }
        }

        return $data;
    }

}
