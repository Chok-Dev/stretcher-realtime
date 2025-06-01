<?php
// app/Models/StretcherRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StretcherRequest extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'my_stretcher';
    protected $primaryKey = 'stretcher_register_id';
    public $timestamps = false;

    protected $fillable = [
        'hn',
        'pname',
        'fname',
        'lname',
        'stretcher_type_name',
        'stretcher_o2tube_type_name',
        'stretcher_priority_name',
        'stretcher_emergency_name',
        'dname',
        'department',
        'department2',
        'from_note',
        'send_note',
        'stretcher_work_status_id',
        'stretcher_work_status_name',
        'stretcher_team_list_id',
        'name',
        'stretcher_register_date',
        'stretcher_register_time',
        'stretcher_register_accept_date',
        'stretcher_register_accept_time',
        'stretcher_register_send_time',
        'stretcher_register_return_time'
    ];

   /*  protected $casts = [
        'stretcher_register_date' => 'date',
        'stretcher_register_accept_date' => 'date'
    ];
 */
    public function getFullNameAttribute()
    {
        return $this->pname . $this->fname . ' ' . $this->lname;
    }

    public function isUrgent()
    {
        return in_array($this->stretcher_priority_name, ['ด่วนที่สุด', 'ด่วน']);
    }

    public function isAvailable()
    {
        return $this->stretcher_work_status_id == 1 && empty($this->stretcher_team_list_id);
    }

    public function isAccepted()
    {
        return $this->stretcher_work_status_id == 2;
    }

    public function isInProgress()
    {
        return $this->stretcher_work_status_id == 3;
    }

    public function isCompleted()
    {
        return $this->stretcher_work_status_id == 4;
    }
}