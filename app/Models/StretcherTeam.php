<?php
// app/Models/StretcherTeam.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StretcherTeam extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'stretcher_team_list';
    protected $primaryKey = 'stretcher_team_list_id';
    public $timestamps = false;

    protected $fillable = [
        'stretcher_team_list_doctor',
        'stretcher_team_list_name',
    ];

    public function stretcherRequests()
    {
        return $this->hasMany(StretcherRegister::class, 'stretcher_team_list_id', 'stretcher_team_list_id');
    }
}