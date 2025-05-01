<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Divisi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'divisis';

    protected $fillable = [
        'name',
        'kode_divisi',
    ];

    protected static function boot()
    {
        parent::boot();

        // Generate kode_divisi saat membuat data baru
        static::creating(function ($model) {
            $model->kode_divisi = $model->generateKodeDivisi();
        });
    }

    public function generateKodeDivisi()
    {
        // Ambil tiga huruf pertama dari name untuk kode singkatan
        $nameSlug = strtoupper(substr(str_replace(' ', '', $this->name), 0, 3));
    
        // Ambil nomor increment terakhir dari database untuk semua kode_divisi
        $lastDivision = self::orderBy('id', 'desc')->first();
    
        // Tentukan nomor berikutnya berdasarkan increment terakhir
        $nextNumber = $lastDivision ? sprintf('%03d', intval(substr($lastDivision->kode_divisi, -3)) + 1) : '001';
    
        return $nameSlug . '-' . $nextNumber;
    }
    
}
