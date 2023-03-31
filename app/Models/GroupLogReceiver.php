<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupLogReceiver extends Model
{
    use HasFactory;
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected $table = 'group_logs_receivers';
}
