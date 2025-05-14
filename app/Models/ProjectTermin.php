<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectTermin extends Model
{
    use HasFactory;

    protected $table = 'projects_termin';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'project_id',
        'harga_termin',
        'deskripsi_termin',
        'type_termin',
        'file_attachment_pembayaran', 
        'tanggal_payment'
    ];

    public function getFileAttachmentPembayaranAttribute($value)
    {
        return is_null($value) ? null : (string) $value;
    }

    /**
     * Relasi ke model Project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    /**
     * Konversi `type_termin` ke dalam format array yang benar.
     */
    public function getTypeTerminAttribute()
    {
        $status = $this->attributes['type_termin'] ?? null;

        if (is_null($status)) {
            return null;
        }

        return [
            'id' => (string) $status,
            'name' => $status == Project::TYPE_TERMIN_PROYEK_LUNAS ? 'Lunas' : 'Belum Lunas',
        ];
    }
}
