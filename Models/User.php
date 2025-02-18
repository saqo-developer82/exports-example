<?php

namespace Exports\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    const ROLE_ADMIN = 1;
    const ROLE_MANAGER = 2;
}
