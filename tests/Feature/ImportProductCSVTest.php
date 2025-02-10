<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mockery;
use App\Models\Product;
use App\Services\ProductService;
use Mockery as GlobalMockery;

class ImportProductCSVTest extends TestCase
{
    /**
     * Test the CSV import command with a mock CSV file.
     *
     * @return void
     */
    public function testImportProductCSV()
    {
        // Create a mock CSV file in memory
        $csvContent = "name,description,price,stock_level,discontinued\n";
        $csvContent .= "Product 1,Description of product 1,10.00,15,no\n";
        $csvContent .= "Product 2,Description of product 2,3.00,5,no\n";
        $csvContent .= "Product 3,Description of product 3,1500.00,10,no\n";
        $csvContent .= "Product 4,Description of product 4,20.00,2,yes\n";

        // Store the mock file temporarily
        $tempFile = storage_path('app/test_products.csv');
        File::put($tempFile, $csvContent);

        // Mock the Product model
        $productMock = GlobalMockery::mock(Product::class);
        $productMock->shouldReceive('create')->once()->with([
            'name' => 'Product 1',
            'description' => 'Description of product 1',
            'price' => 10.00,
            'stock_level' => 15,
            'discontinued_at' => null,
        ])->andReturnSelf();

        // Run the import command with the test file
        $result = Artisan::call('import:csv', ['file' => $tempFile]);

        // Assert that the command produces the correct output
        $this->assertStringContainsString('Import Summary', $result);
        $this->assertStringContainsString('Success: 1', $result);
        $this->assertStringContainsString('Skipped: 2', $result);
        $this->assertStringContainsString('Failed: 0', $result);

        // Cleanup the file
        File::delete($tempFile);
    }

    /**
     * Test the product import command logic for skipped items.
     *
     * @return void
     */
    public function testShouldSkipItemBasedOnBusinessRules()
    {
        $productService = new ProductService();

        // Mock the CSV file data
        $record = [
            'name' => 'Product 2',
            'description' => 'Description of product 2',
            'price' => '3.00',
            'stock_level' => '5',
            'discontinued' => 'no'
        ];

        // Check if the item should be skipped based on the business rules
        $shouldSkip = $productService->shouldSkipImport($record);

        // Assert that the item should be skipped (price < 5 and stock < 10)
        $this->assertTrue($shouldSkip);
    }

    /**
     * Test that products marked as discontinued have the discontinued date set.
     *
     * @return void
     */
    public function testDiscontinuedProductHasDateSet()
    {
        $productService = new ProductService();

        $record = [
            'name' => 'Product 4',
            'description' => 'Description of discontinued product',
            'price' => '20.00',
            'stock_level' => '2',
            'discontinued' => 'yes',
        ];

        // Check if the discontinued date is set correctly
        $discontinuedDate = $productService->getDiscontinuedDate($record);

        // Assert that the discontinued date is not null
        $this->assertNotNull($discontinuedDate);
    }

    /**
     * Test the import process without inserting data (test mode).
     *
     * @return void
     */
    public function testImportInTestMode()
    {
        // Mock a test file
        $csvContent = "name,description,price,stock_level,discontinued\n";
        $csvContent .= "Product 1,Description of product 1,10.00,15,no\n";

        // Store the mock file temporarily
        $tempFile = storage_path('app/test_products.csv');
        File::put($tempFile, $csvContent);

        // Run the import command in test mode (no database insertions)
        $result = Artisan::call('import:csv', [
            'file' => $tempFile,
            '--test' => true
        ]);

        // Assert that the command processes the data correctly in test mode
        $this->assertStringContainsString('Test mode: ', $result);
        $this->assertStringContainsString('Success: 1', $result);
        $this->assertStringContainsString('Skipped: 0', $result);
        $this->assertStringContainsString('Failed: 0', $result);

        // Cleanup the file
        File::delete($tempFile);
    }
}
