<?php
namespace AstraTech\DataForge\Base;

use Illuminate\Database\Eloquent\Model;
class BaseModel extends Model
{
    public $timestamps = false;
    public function __construct($table = null, array $attributes = [])
    {
        parent::__construct($attributes);
        if ($table) {
            $this->setTable($table);
        }
    }
    public function setPrimaryKey($key)
    {
        if ($key)
            $this->primaryKey = $key;
    }
}