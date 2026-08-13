<?php

namespace App\Repositories;

use App\Models\Admin;

class AdminRepository
{
    public function findByEmail(string $email): ?Admin
    {
        return Admin::where('email', $email)->first();
    }

    public function findById(int $id): ?Admin
    {
        return Admin::find($id);
    }
}
