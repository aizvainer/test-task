<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreatedCsv extends Model
{
    public $timestamps = false;

    protected $fillable = ['shipping_id', 'csv_status', 'creation_date'];
}
