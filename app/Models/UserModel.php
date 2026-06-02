<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true; // Auto-handles created_at and updated_at
    
    // Explicit safety baseline: Protect database from mass-assignment exploits
    protected $allowedFields    = ['name', 'email', 'avatar'];
}