<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProblemReport extends Model {
    protected $fillable = ['user_id', 'reporter_name', 'contact', 'category', 'description', 'page_path', 'screenshot_path', 'status'];
    public const CATEGORIES = ['not_working' => 'Something is not working', 'incorrect' => 'Information looks incorrect', 'cannot_find' => 'I cannot find something', 'other' => 'Something else'];
    public const STATUSES = ['new' => 'New', 'checking' => 'Being checked', 'resolved' => 'Resolved'];
}
