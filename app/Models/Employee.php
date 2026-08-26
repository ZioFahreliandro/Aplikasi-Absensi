<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = ['name', 'nip', 'phone', 'birth_date', 'password', 'must_change_password'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'must_change_password' => 'boolean',
            'birth_date' => 'date',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the attendances for the employee.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
