<?php
// app/Models/StretcherRegister.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StretcherRegister extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'stretcher_register';
    protected $primaryKey = 'stretcher_register_id';
    public $timestamps = false;

    protected $fillable = [
        'hn',
        'stretcher_type_id',
        'stretcher_priority_id',
        'stretcher_register_date',
        'stretcher_register_time',
        'stretcher_register_accept_date',
        'stretcher_register_accept_time',
        'stretcher_register_send_time',
        'stretcher_register_return_time',
        'stretcher_work_status_id',
        'stretcher_team_list_id',
        'stretcher_service_id',
        'stretcher_work_result_id',
        'lastupdate',
        'from_note',
        'send_note',
    ];

    protected $casts = [
        'stretcher_register_date' => 'date',
        'stretcher_register_accept_date' => 'date',
        'lastupdate' => 'datetime',
    ];

    // Relationships
    public function team()
    {
        return $this->belongsTo(StretcherTeam::class, 'stretcher_team_list_id', 'stretcher_team_list_id');
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('stretcher_register_date', today());
    }

    public function scopeAvailable($query)
    {
        return $query->whereNull('stretcher_team_list_id')
                    ->where('stretcher_work_status_id', 1);
    }

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('stretcher_team_list_id', $teamId);
    }
}