<?php

namespace App\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    const ATTACHMENT_FILE = 'attachment/project/file';
    const ATTACHMENT_FILE_TERMIN_PROYEK = 'attachment/project/terminproyek_file';

    // Status Cost Progress
    const STATUS_OPEN = 'OPEN';
    const STATUS_CLOSED = 'CLOSED';
    const STATUS_NEED_TO_CHECK = 'NEED TO CHECK';

    // Status Request Owner
    const PENDING = 1;
    const ACTIVE = 2;
    const REJECTED = 3;
    const CLOSED = 4;
    const CANCEL = 5;

    // Status Bonus
    const BELUM_DIKASIH_BONUS = 1;
    const SUDAH_DIKASIH_BONUS = 2;

    // Status Type Project
    const HIK = 1;
    const DWI = 2;

    const TYPE_TERMIN_PROYEK_BELUM_LUNAS = 1;
    const TYPE_TERMIN_PROYEK_LUNAS = 2;

    const DEFAULT_STATUS_NO_BONUS = self::BELUM_DIKASIH_BONUS;
    const DEFAULT_STATUS = self::PENDING;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'projects';

    protected $fillable = [
        'id',
        'company_id',
        'user_id',
        'name',
        'billing',
        'cost_estimate',
        'margin',
        'percent',
        'status_cost_progres',
        'file',
        'date',
        'request_status_owner',
        'status_bonus_project',
        'type_projects',
        'no_dokumen_project',
        'file_pembayaran_termin',
        'deskripsi_termin_proyek',
        'type_termin_proyek',
        'harga_termin_proyek',
        'payment_date_termin_proyek'
    ];

    public static function getTypeProjectsOptions()
    {
        return [
            self::HIK => 'HIK',
            self::DWI => 'DWI',
        ];
    }

    // Project.php (Model)
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Ambil tahun dari input 'date' yang dimasukkan oleh pengguna
            $year = date('y', strtotime($model->date)); // Ambil tahun dari input tanggal

            // Tentukan ID proyek berdasarkan tahun dan nomor urut
            $model->id = 'PRO-' . $year . '-' . $model->generateSequenceNumber($year);

            $model->request_status_owner = self::DEFAULT_STATUS;
            // $model->status_step_project = self::DEFAULT_STATUS_PROJECT;
        });
    }

    protected function generateSequenceNumber($year)
    {
        // Ambil ID proyek terakhir untuk tahun yang sama
        $lastId = static::where('id', 'like', 'PRO-' . $year . '%')->max('id');

        if ($lastId) {
            // Ambil nomor urut dari ID terakhir dan increment
            $numericPart = (int) substr($lastId, strrpos($lastId, '-') + 1);
            $nextNumber = sprintf('%03d', $numericPart + 1); // Increment dan pad dengan 0s
        } else {
            // Jika belum ada, mulai dari nomor urut 001
            $nextNumber = '001';
        }

        return $nextNumber;
    }

    protected $appends = ['latest_payment_date'];

    public function projectTermins(): HasMany
    {
        return $this->hasMany(ProjectTermin::class, 'project_id', 'id')
            ->orderBy('tanggal_payment', 'desc') // Urutkan berdasarkan tanggal pembayaran
            ->orderBy('created_at', 'desc'); // Jika tanggal sama, ambil yang terbaru berdasarkan waktu input
    }


    public function getLatestPaymentDateAttribute()
    {
        return optional($this->projectTermins()->first())->tanggal_payment;
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function tenagaKerja()
    {
        return $this->belongsToMany(User::class, 'projects_user_tasks', 'project_id', 'user_id');
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'projects_user_tasks', 'project_id', 'tasks_id');
    }

    public function tasksDirect()
    {
        return $this->hasMany(Task::class, 'project_id', 'id');
    }

    public function budgetsDirect()
    {
        return $this->hasMany(Budget::class, 'project_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /* Opsional Relasi */
    public function absensiUsers()
    {
        return $this->hasMany(UserProjectAbsen::class, 'project_id', 'id');
    }

    public function locations()
    {
        return $this->hasMany(ProjectHasLocation::class, 'project_id', 'id');
    }

     public function purchases()
    {
        return $this->hasMany(Purchase::class, 'project_id', 'id');
    }

    /** Payroll-payroll tenaga kerja proyek (kolom `project_id` di tabel payrolls) */
    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'project_id', 'id');
    }
}
