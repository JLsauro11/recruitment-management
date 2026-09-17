<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AssessmentResult extends Model {
    protected $fillable=['application_id','overall_score','fit_label','confidence','category_scores','strengths','gaps','interview_focus','summary','assessed_at'];
    protected $casts=['category_scores'=>'array','strengths'=>'array','gaps'=>'array','interview_focus'=>'array','assessed_at'=>'datetime','overall_score'=>'float'];
    public function application(){ return $this->belongsTo(Application::class); }
}
