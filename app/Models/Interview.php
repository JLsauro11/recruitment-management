<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Interview extends Model { use HasFactory; protected $fillable=['application_id','interviewer_id','type','scheduled_at','location','meeting_link','status','remarks']; protected $casts=['scheduled_at'=>'datetime']; public function application(){return $this->belongsTo(Application::class);} public function interviewer(){return $this->belongsTo(User::class,'interviewer_id');} }
