<?php
namespace Ry\Wpblog\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
	protected $connection = "wpblog";
	
	protected $table = "terms";
}