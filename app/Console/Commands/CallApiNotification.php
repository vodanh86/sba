<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;

class CallApiNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:call';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notifications';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $client = new Client();
        $response = $client->get('http://127.0.0.1:8000/api/notifications');
        if ($response->getStatusCode() === 200) {
            $this->info('API call successful');
        } else {
            $this->error('API call failed');
        }
    }
}
