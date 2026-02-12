<?php

namespace Blaspsoft\Blasp\Laravel\Events;

use Blaspsoft\Blasp\Core\Result;
use Illuminate\Http\Request;

class ContentBlocked
{
    public function __construct(
        public readonly Result $result,
        public readonly Request $request,
        public readonly string $field,
        public readonly string $action,
    ) {}
}
