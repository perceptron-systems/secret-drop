<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('secrets:clean')->hourly();
Schedule::command('secrets:clean-blobs')->everySixHours();
Schedule::command('session:gc')->daily();
