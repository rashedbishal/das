<?php

namespace App\Console\Commands;

use Database\Seeders\ClientCatalogSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('catalog:import-client-data {--force : Skip the confirmation prompt}')]
#[Description('Replace catalog records with the reviewed client product data')]
class ImportClientCatalog extends Command
{
    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'This permanently replaces all products, categories, subcategories, brands, and tags. It also sets the documented catalog delivery note. User accounts, homepage sections, and other settings are kept. Continue?',
        )) {
            $this->components->warn('Catalog import cancelled.');

            return self::SUCCESS;
        }

        app(ClientCatalogSeeder::class)->run();

        $this->components->info('Imported 15 client products and their photos, specifications, and options.');

        return self::SUCCESS;
    }
}
