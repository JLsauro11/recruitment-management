<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Exam extends Model
{
    protected $fillable = ['title','type','total_score','passing_score','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function results(){ return $this->hasMany(ExamResult::class); }
}
