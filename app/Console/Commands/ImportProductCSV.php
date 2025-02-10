<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;
use Carbon\Carbon;

class ImportProductCSV extends Command
{
    protected $signature = 'import:csv {file} {--test}';
    protected $description = 'Import product data from a CSV file';

    public function handle()
    {
        // Create product service object
        $productService = new ProductService();

        // Get parameters
        $filePath = $this->argument('file');
        $isTestMode = $this->option('test');
        $skipped = 0;
        $success = 0;
        $failed = 0;

        // Load the CSV file using League CSV
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        // Assume first row is headers
        $records = $csv->getRecords();

        foreach ($records as $record) {
            // Business rules
            if ($productService->shouldSkipImport($record)) {
                $skipped++;
                continue;
            }

            // Create or update the product record
            try {
                $productData = [
                    'name' => $record['name'],
                    'description' => $record['description'],
                    'stock_level' => $record['stock_level'],
                    'price' => $record['price'],
                    'discontinued_at' => $productService->getDiscontinuedDate($record),
                ];

                if ($isTestMode) {
                    $this->info('Test mode: ' . json_encode($productData));
                    $success++;
                    continue;
                }

                Product::create($productData);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $this->error("Failed to import product: " . $record['name']);
            }
        }

        // Report summary
        $this->info("Import Summary:");
        $this->info("Total Records Processed: " . ($skipped + $success + $failed));
        $this->info("Success: $success");
        $this->info("Skipped: $skipped");
        $this->info("Failed: $failed");
    }
}
