<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\RecordsClinicalAudit;
class ProgressNote extends Model
{
    use RecordsClinicalAudit;

    protected $fillable = ['treatment_id', 'note', 'author_id'];
}
