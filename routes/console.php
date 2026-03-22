<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('recordings:process-media --limit=50')->everyFiveMinutes();
Schedule::command('recordings:process-ai --limit=50')->everyFiveMinutes();
Schedule::command('workflows:generate --limit=50')->everyFiveMinutes();
Schedule::command('stats:aggregate')->hourly();
Schedule::command('billing:reconcile')->everyMinute();
Schedule::command('recordings:cleanup-storage --days=30')->daily();
