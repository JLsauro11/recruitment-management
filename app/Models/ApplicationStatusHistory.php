<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ApplicationStatusHistory extends Model { use HasFactory; protected $fillable=['application_id','status','remarks','updated_by']; public function application(){return $this->belongsTo(Application::class);} }
