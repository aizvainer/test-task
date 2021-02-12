<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreatedCsv extends Model
{

    protected $fillable = ['shipping_id', 'csv_status'];

    protected $casts = ['csv_status' => 'boolean'];
}
