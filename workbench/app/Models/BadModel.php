<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

class BadModel extends Model
{
    // ❌ Anti-pattern: Missing $fillable or $guarded

    // ❌ Let's make it slightly fat by adding random lines
    public function someMethod()
    {
        return true;
    }
}
