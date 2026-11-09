<?php

namespace Bale\Pelatihan\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PublishConfigPelatihan extends Command
{
    protected $signature = 'pelatihan:publish-config {--force : Overwrite existing configuration file}';

    protected $description = 'Publish configuration file for bale/pelatihan';

    public function handle(): int
    {
        $source = __DIR__.'/../../config/pelatihan.php';
        $target = config_path('pelatihan.php');

        if (! File::exists($source)) {
            $this->error("Config source not found: {$source}");

            return self::FAILURE;
        }

        if (File::exists($target) && ! $this->option('force')) {
            $this->line('Config file already exists: <comment>config/pelatihan.php</comment>. Use <info>--force</info> to overwrite.');

            return self::SUCCESS;
        }

        File::copy($source, $target);
        $this->info('Published config file: <info>config/pelatihan.php</info>');

        return self::SUCCESS;
    }
}
