<?php
// app/Models/MyStretcher.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MyStretcher extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'my_stretcher';
    protected $primaryKey = 'stretcher_register_id';
    public $timestamps = false;

    protected $fillable = [
        'stretcher_register_id',
        'hn',
        'pname',
        'fname',
        'lname',
        'stretcher_type_name',
        'stretcher_o2tube_type_name',
        'stretcher_priority_name',
        'stretcher_work_status_name',
        'dname',
        'department',
        'department2',
        'from_note',
        'send_note',
        'name',
        'stretcher_register_date',
        'stretcher_register_time',
        'stretcher_work_status_id',
        'stretcher_team_list_id',
    ];

    /* protected $casts = [
        'stretcher_register_date' => 'date',
        'stretcher_register_time' => 'time',
    ]; */

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('stretcher_register_date', today());
    }

    public function scopeNewRequests($query)
    {
        return $query->where('stretcher_work_status_id', 1);
    }

    public function scopeCompleted($query)
    {
        return $query->where('stretcher_work_status_id', 4);
    }
}