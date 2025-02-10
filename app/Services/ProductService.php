<?php

namespace App\Services;

use Carbon\Carbon;

class ProductService
{

    /**
     * Check business rules for skipping the product
     *
     * @param array $record Product object
     *
     * @return bool
     */
    public function shouldSkipImport($record)
    {
        $price = (float)$record['price'];
        $stockLevel = (int)$record['stock_level'];

        // Skip items under $5 and stock less than 10
        if ($price < 5 && $stockLevel < 10) {
            return true;
        }

        // Skip items over $1000
        if ($price > 1000) {
            return true;
        }

        return false;
    }

    /**
     * Get the discontinued date
     *
     * @param array $record Product object
     *
     * @return string|mixed
     */
    public function getDiscontinuedDate($record)
    {
        return $record['discontinued'] === 'yes' ? Carbon::now() : null;
    }
}
