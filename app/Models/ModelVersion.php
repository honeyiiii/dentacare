<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ModelVersion extends Model
{
    protected $fillable = ['version', 'algorithm', 'artifact_path', 'metadata', 'trained_at'];
    protected function casts(): array { return ['metadata' => 'array', 'trained_at' => 'datetime']; }
}
