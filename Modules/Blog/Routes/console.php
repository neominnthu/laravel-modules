<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('blog:hello', function () {
    $this->comment('Hello from Blog module');
});
