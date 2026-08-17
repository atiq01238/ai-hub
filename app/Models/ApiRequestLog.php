<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    protected $fillable = ['provider', 'endpoint', 'method', 'status_code', 'duration_ms', 'successful', 'error_message'];
    protected $casts = ['successful' => 'boolean', 'duration_ms' => 'integer', 'status_code' => 'integer'];
}
