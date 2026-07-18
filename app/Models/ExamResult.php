<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ExamResult extends Model { use HasFactory; protected $fillable=['application_id','exam_type','score','passing_score','result','remarks']; public function application(){return $this->belongsTo(Application::class);} }
