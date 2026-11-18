<?php

namespace App\Console\Commands;

use App\Support\RobotsTxt;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Hostinger's web server serves .txt files only from disk and never hands a missing one to Laravel,
 * so the robots.txt route is unreachable there. The deploy runs this to write the same body to public/.
 */
#[Signature('das:write-robots-txt')]
#[Description('Write public/robots.txt for web servers that do not pass robots.txt to Laravel')]
class WriteRobotsTxt extends Command
{
    public function handle(): int
    {
        $path = public_path('robots.txt');

        if (file_put_contents($path, RobotsTxt::contents()) === false) {
            $this->components->error("Could not write {$path}.");

            return self::FAILURE;
        }

        $this->components->info("Wrote {$path}.");

        return self::SUCCESS;
    }
}
