<?php

namespace App\Services;

use App\Models\SpreadsheetId;

class CurrencyService
{
    /**
     * Get the currency collection based on the currency type.
     * 
     * @param string $curr
     * @return array|null
     */
    public function currencyCollection($curr, $brand)
    {
        // dd($curr, $brand);
        $currencyType = SpreadsheetId::where('brand', $brand)
            ->where('currencyType', $curr)
            ->where('is_active', true)
            ->get();

        // Group the result into the required format
        $formattedResult = [
            'index' => $currencyType->first()->index ?? null,  // Get the index from the first record, or null if not found
            'keywords' => $currencyType->pluck('sid')->all(),  // Get all keywords as an array
        ];
        // dd($formattedResult);
        return $formattedResult ?? null;
    }
    /**
     * Get the spreedsheet info based on the currency type.
     * 
     * @param string $sid
     * @return array|null
     */
    public function spreedsheetId($sid, $brand)
    {
        // dd($sid, $brand);
        $currencyType = SpreadsheetId::where('brand', $brand)
            ->where('sid', $sid)
            ->where('is_active', true)
            ->first();
        // Group the result into the required format
        $formattedResult = [
            'spreed_id' => $currencyType->spread_id,
            'platform' => $currencyType->platform
        ];
        // dd($formattedResult);
        return $formattedResult ?? null;
    }
}
