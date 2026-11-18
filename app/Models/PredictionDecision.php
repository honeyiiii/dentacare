<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PredictionDecision extends Model
{
    protected $fillable = ['prediction_id', 'dentist_id', 'decision', 'note'];
}
