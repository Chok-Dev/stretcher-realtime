<?php
// app/Models/StretcherRegister.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StretcherRegister extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'stretcher_register';
    protected $primaryKey = 'stretcher_register_id';
    public $timestamps = false;

    protected $fillable = [
        'hn',
        'pname',
        'fname',
        'lname',
        'stretcher_type_id',
        'stretcher_o2tube_type_id',
        'stretcher_priority_id',
        'stretcher_emergency_id',
        'stretcher_work_status_id',
        'stretcher_team_list_id',
        'department_id',
        'department_id2',
        'from_note',
        'send_note',
        'stretcher_register_date',
        'stretcher_register_time',
        'stretcher_register_accept_date',
        'stretcher_register_accept_time',
        'stretcher_register_send_time',
        'stretcher_register_return_time',
        'user_id',
        'lastupdate'
    ];

    /**
     * Get full stretcher request data with names
     */
    public function getFullDataAttribute()
    {
        return DB::connection('pgsql')
            ->table('my_stretcher')
            ->where('stretcher_register_id', $this->stretcher_register_id)
            ->first();
    }

    /**
     * Get formatted data for notifications
     */
    public function getNotificationDataAttribute()
    {
        $fullData = $this->full_data;
        
        if (!$fullData) {
            return [
                'stretcher_register_id' => $this->stretcher_register_id,
                'hn' => $this->hn ?? 'N/A',
                'pname' => $this->pname ?? '',
                'fname' => $this->fname ?? 'N/A',
                'lname' => $this->lname ?? '',
                'stretcher_priority_name' => 'ไม่ระบุ',
                'stretcher_type_name' => 'ไม่ระบุ',
                'stretcher_o2tube_type_name' => '',
                'stretcher_emergency_name' => '',
                'department' => 'ไม่ระบุ',
                'department2' => 'ไม่ระบุ',
                'dname' => 'ไม่ระบุ',
                'from_note' => $this->from_note ?? '',
                'send_note' => $this->send_note ?? '',
                'stretcher_register_date' => $this->stretcher_register_date,
                'stretcher_register_time' => $this->stretcher_register_time
            ];
        }

        return (array) $fullData;
    }

    /**
     * Check if this is urgent request
     */
    public function isUrgent()
    {
        $fullData = $this->full_data;
        if (!$fullData) return false;
        
        return in_array($fullData->stretcher_priority_name, ['ด่วนที่สุด', 'ด่วน']) 
               || !empty($fullData->stretcher_emergency_name);
    }

    /**
     * Check if this is new request (created today and no team assigned)
     */
    public function isNewRequest()
    {
        return $this->stretcher_register_date === now()->format('Y-m-d') 
               && empty($this->stretcher_team_list_id)
               && $this->stretcher_work_status_id == 1;
    }
}