<?php
// app/Console/Commands/ReverbStartCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class ReverbStartCommand extends Command
{
    protected $signature = 'stretcher:reverb-start 
                            {--host=127.0.0.1 : The IP address to bind to}
                            {--port=8080 : The port to listen on}
                            {--debug : Enable debug mode}
                            {--watch : Watch for file changes and auto-restart}
                            {--production : Run in production mode}';

    protected $description = 'Start Laravel Reverb WebSocket server for Stretcher system';

    public function handle()
    {
        $host = $this->option('host');
        $port = $this->option('port');
        $debug = $this->option('debug');
        $watch = $this->option('watch');
        $production = $this->option('production');

        $this->displayBanner();
        $this->checkRequirements();
        
        if ($production) {
            $this->runProductionMode($host, $port);
        } elseif ($watch) {
            $this->runWatchMode($host, $port, $debug);
        } else {
            $this->runDevelopmentMode($host, $port, $debug);
        }
    }

    private function displayBanner()
    {
        $this->line('');
        $this->line('<fg=cyan>╔══════════════════════════════════════════════════════════════╗</>');
        $this->line('<fg=cyan>║</> <fg=white;options=bold>      ศูนย์เปล - WebSocket Server (Laravel Reverb)        </> <fg=cyan>║</>');
        $this->line('<fg=cyan>║</> <fg=yellow>             Real-time Stretcher Management System        </> <fg=cyan>║</>');
        $this->line('<fg=cyan>╚══════════════════════════════════════════════════════════════╝</>');
        $this->line('');
    }

    private function checkRequirements()
    {
        $this->info('🔍 Checking system requirements...');
        
        // Check if Reverb is installed
        if (!class_exists('\Laravel\Reverb\ReverbServiceProvider')) {
            $this->error('❌ Laravel Reverb is not installed. Please run: composer require laravel/reverb');
            exit(1);
        }
        
        // Check configuration
        if (!config('broadcasting.connections.reverb')) {
            $this->error('❌ Reverb configuration not found. Please publish config: php artisan reverb:install');
            exit(1);
        }
        
        // Check Redis connection (if scaling enabled)
        if (config('reverb.servers.reverb.scaling.enabled', false)) {
            try {
                \Redis::connection()->ping();
                $this->line('<fg=green>✅ Redis connection: OK</fg=green>');
            } catch (\Exception $e) {
                $this->warn('⚠️  Redis connection failed. Scaling features will be disabled.');
                $this->line("   Error: {$e->getMessage()}");
            }
        }
        
        // Check environment variables
        $requiredEnvVars = ['REVERB_APP_ID', 'REVERB_APP_KEY', 'REVERB_APP_SECRET'];
        foreach ($requiredEnvVars as $var) {
            if (!env($var)) {
                $this->error("❌ Required environment variable {$var} is not set.");
                exit(1);
            }
        }
        
        $this->line('<fg=green>✅ All requirements satisfied</fg=green>');
        $this->line('');
    }

    private function runDevelopmentMode($host, $port, $debug)
    {
        $this->info('🚀 Starting Reverb server in development mode...');
        $this->line("📡 WebSocket Server: {$host}:{$port}");
        $this->line("🔧 Debug mode: " . ($debug ? 'Enabled' : 'Disabled'));
        $this->line("🌍 Environment: " . config('app.env'));
        $this->line('');
        
        $command = [
            'php',
            'artisan',
            'reverb:start',
            '--host=' . $host,
            '--port=' . $port,
        ];
        
        if ($debug) {
            $command[] = '--debug';
        }
        
        $this->line('<fg=yellow>Press Ctrl+C to stop the server</fg=yellow>');
        $this->line('');
        
        // Log the start
        Log::info('Reverb server starting', [
            'host' => $host,
            'port' => $port,
            'debug' => $debug,
            'mode' => 'development'
        ]);
        
        // Start the server
        $process = Process::start(implode(' ', $command));
        
        // Handle the process output
        $process->wait(function ($type, $buffer) {
            if ($type === Process::ERR) {
                $this->error(trim($buffer));
            } else {
                $this->line(trim($buffer));
            }
        });
    }

    private function runWatchMode($host, $port, $debug)
    {
        $this->info('👀 Starting Reverb server in watch mode...');
        $this->warn('⚠️  Watch mode will restart the server when files change');
        $this->line("📡 WebSocket Server: {$host}:{$port}");
        $this->line('');
        
        // Check if nodemon is available
        $nodemonCheck = Process::run('which nodemon');
        if (!$nodemonCheck->successful()) {
            $this->error('❌ nodemon is required for watch mode. Install with: npm install -g nodemon');
            exit(1);
        }
        
        $command = [
            'nodemon',
            '--watch', 'app/',
            '--watch', 'config/',
            '--watch', 'routes/',
            '--ext', 'php',
            '--exec', "php artisan reverb:start --host={$host} --port={$port}" . ($debug ? ' --debug' : ''),
        ];
        
        Log::info('Reverb server starting in watch mode', [
            'host' => $host,
            'port' => $port,
            'debug' => $debug,
            'mode' => 'watch'
        ]);
        
        $this->line('<fg=yellow>Press Ctrl+C to stop the watcher</fg=yellow>');
        $this->line('');
        
        $process = Process::start(implode(' ', $command));
        $process->wait(function ($type, $buffer) {
            $this->line(trim($buffer));
        });
    }

    private function runProductionMode($host, $port)
    {
        $this->info('🏭 Starting Reverb server in production mode...');
        $this->line("📡 WebSocket Server: {$host}:{$port}");
        $this->line('');
        
        // Check if supervisor or systemd is available
        $this->warn('⚠️  For production, consider using a process manager like Supervisor or systemd');
        $this->line('');
        
        $command = [
            'php',
            'artisan',
            'reverb:start',
            '--host=' . $host,
            '--port=' . $port,
            '--no-debug',
        ];
        
        Log::info('Reverb server starting in production mode', [
            'host' => $host,
            'port' => $port,
            'mode' => 'production'
        ]);
        
        $this->displayProductionInstructions($host, $port);
        
        $process = Process::start(implode(' ', $command));
        $process->wait(function ($type, $buffer) {
            if ($type === Process::ERR) {
                Log::error('Reverb server error: ' . trim($buffer));
                $this->error(trim($buffer));
            } else {
                $this->line(trim($buffer));
            }
        });
    }

    private function displayProductionInstructions($host, $port)
    {
        $this->line('<fg=blue>📋 Production Setup Instructions:</fg=blue>');
        $this->line('');
        
        $this->line('<fg=yellow>1. Supervisor Configuration (/etc/supervisor/conf.d/reverb.conf):</fg=yellow>');
        $this->line('[program:reverb]');
        $this->line('command=php ' . base_path() . '/artisan reverb:start --host=' . $host . ' --port=' . $port);
        $this->line('directory=' . base_path());
        $this->line('autostart=true');
        $this->line('autorestart=true');
        $this->line('user=www-data');
        $this->line('redirect_stderr=true');
        $this->line('stdout_logfile=' . storage_path('logs/reverb.log'));
        $this->line('');
        
        $this->line('<fg=yellow>2. Systemd Service (/etc/systemd/system/reverb.service):</fg=yellow>');
        $this->line('[Unit]');
        $this->line('Description=Laravel Reverb WebSocket Server');
        $this->line('After=network.target');
        $this->line('');
        $this->line('[Service]');
        $this->line('Type=simple');
        $this->line('User=www-data');
        $this->line('WorkingDirectory=' . base_path());
        $this->line('ExecStart=/usr/bin/php artisan reverb:start --host=' . $host . ' --port=' . $port);
        $this->line('Restart=always');
        $this->line('RestartSec=10');
        $this->line('');
        $this->line('[Install]');
        $this->line('WantedBy=multi-user.target');
        $this->line('');
        
        $this->line('<fg=yellow>3. Nginx Configuration (proxy WebSocket):</fg=yellow>');
        $this->line('location /ws {');
        $this->line('    proxy_pass http://127.0.0.1:' . $port . ';');
        $this->line('    proxy_http_version 1.1;');
        $this->line('    proxy_set_header Upgrade $http_upgrade;');
        $this->line('    proxy_set_header Connection "upgrade";');
        $this->line('    proxy_set_header Host $host;');
        $this->line('    proxy_cache_bypass $http_upgrade;');
        $this->line('}');
        $this->line('');
    }
}