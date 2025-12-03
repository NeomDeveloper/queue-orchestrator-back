<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Http;

Route::get('/test', function () {
    return [
        'status' => 'ok',
        'time' => now(),
    ];
});

Route::get('/tasks', function () {
    return DB::table('jobs')
            ->selectRaw('count(available_at) as pending, count(reserved_at) as running, queue')
            ->groupBy('queue')
            ->get()
            ->map(function ($job){
                $processPsCheck = Process::fromShellCommandline(
                    "ps aux | grep 'artisan queue:work database --queue={$job->queue} --' | grep -v grep | wc -l"
                );

                $processPsCheck->run();

                // Get output
                $output = $processPsCheck->getOutput();

                return [
                    'queue' => $job->queue,
                    'pending' => $job->pending,
                    'running' => $job->running,
                    'workers_running' => (int)str_replace("\n","",$output),
                    'outpu'=> $output
                ];
            });

});

Route::get('/tasks/create/', function () {
    foreach ([
        'large-tasks'
    ] as $queueName) {
        foreach (range(1, 5) as $i){
            App\Jobs\LargeTask::dispatch()
                    ->onConnection('database')->onQueue($queueName);

            $broadcast = Http::post('http://realtime:3001/broadcast', [
                'channel' => 'tasks',
                'event' => 'TaskCreated',
                'task' => [
                    "queue" => $queueName
                ]
            ])->body();

        }
    }
    return ['status' => 'ok'];
});


Route::post('/execute/{queue}', function (string $queue) {
    if(DB::table('jobs')->where('queue', $queue)->exists()) {
        
        $php = trim(shell_exec('which php'));

        $process = Process::fromShellCommandline(
            implode(" ", [
                "$php",
                base_path('artisan'),
                "queue:work",
                "database",
                "--queue=$queue",
                "--stop-when-empty"
            ])
        );
        $process->disableOutput();
        $process->start();

        return [
            'status' => 'started',
            'queue' => $queue
        ];
    }
    return [
        'status' => 'no jobs found',
        'queue' => $queue,
    ];
});
