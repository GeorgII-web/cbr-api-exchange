<?php
/*
 * Copyright (c) 2021. GeorgII. george.webfullstack@gmail.com
 */

declare(strict_types=1);

namespace CbrApiExchange;

use CbrApiExchange\Contracts\ApiInterface;
use CbrApiExchange\Exceptions\BadFormatException;
use CbrApiExchange\Exceptions\ResponseException;
use CbrApiExchange\Exceptions\StillNoTomorrowExchangeException;
use CbrApiExchange\Exceptions\XmlException;
use Exception;
use Carbon\Carbon;
use InvalidArgumentException;
// use JetBrains\PhpStorm\ArrayShape;
use LibXMLError;
use SimpleXMLElement;

/**
 * Exchange rates class, gets currency rate from Russian Central Bank API, http://www.cbr.ru.
 *
 * Examples:
 * CbrApiExchange::get();
 * CbrApiExchange::get('R01239');
 * CbrApiExchange::get('R01239', '2021-01-05');
 *
 * All currencies list: http://www.cbr.ru/scripts/XML_val.asp?d=0
 * Get USD rate: http://www.cbr.ru/scripts/XML_dynamic.asp?date_req1=01/01/2021&date_req2=12/01/2021&VAL_NM_RQ=R01235
 */
class CbrApiExchange implements ApiInterface
{
    /**
     * Get the currency rate on specified date from Central Bank API.
     * The rate for the this day is announced yesterday (before 15:00MSK) by Central Bank.
     * If no rate announced for this day (holidays) than it used last announced rate.
     *
     * @param string      $currency Currency code. 'R01235' for USD,'R01239' for EUR,'R01035' for GBP.
     * @param string|null $date     Date of the currency rate '2021-01-01', today is the default value
     * @return array Array: (string)'code' of the currency, (string)'date' of the last response rate value, (float)'rate' of the last response value
     * @throws BadFormatException Currency rate has not the price format
     * @throws ResponseException Empty API response
     * @throws StillNoTomorrowExchangeException No exchange rate for tomorrow, wait announcement
     * @throws XmlException XML format errors
     * @throws Exception General exceptions
     */
    // #[ArrayShape([
    //     'code' => 'string', // Code of the requested currency, 'R01235'->USD
    //     'date' => 'string', // Date of the last response value, '2021-01-01'
    //     'rate' => 'float' // Rate of the last response value, 73.8757
    // ])]
    public static function get(string $currency = 'R01235', string $date = null): array
    {
        // Check inputted data
        $dateStr = self::checkInputDate($date);
        $currencyCode = self::checkInputCurrency($currency);

        // Create cbr.ru API specific format url
        $cbrUrl = self::createCbrApiUrl(
            Carbon::parse($dateStr)->subMonths(),
            Carbon::parse($dateStr),
            $currencyCode
        );

        // Make request to cbr.ru API
        $response = self::makeCbrApiRequest($cbrUrl);

        // Response has list of currencies on each date - get last
        [$lastDate, $lastRate] = self::getLastRecord($response);

        // Check currency rate for tomorrow date
        if (self::isTomorrowCurrencyRateExist($dateStr, (string)$lastDate) === false) {
            throw new StillNoTomorrowExchangeException('The exchange rate for tomorrow has not yet been announced.');
        }

        // Check rate format
        if (!self::isPriceFormat((string)$lastRate)) {
            throw new BadFormatException('Currency rate has not the price format.');
        }

        return [
            'code' => $currency,
            'date' => $lastDate,
            'rate' => $lastRate,
        ];
    }


    /**
     * Check and fix inputted date.
     *
     * @param string|null $date Inputted date string
     * @return string Fixed date string '2021-01-01'
     */
    protected static function checkInputDate(string $date = null): string
    {
        // Check date validity
        if ($date) {
            try {
                $parts = explode('-', $date);
                $dateObj = Carbon::createSafe((int)$parts[0], (int)$parts[1], (int)$parts[2]);
                if ($dateObj) {
                    $date = $dateObj->format("Y-m-d");
                }
            } catch (Exception $e) {
                throw new InvalidArgumentException('Invalid date format.');
            }
        } else {
            // Default date NOW
            $date = Carbon::now()->format("Y-m-d");
        }

        // Only tomorrow date in the future allowed
        $point = Carbon::parse($date);
        if (($point->isFuture()) && ($point->diffInDays(Carbon::today()) > 1)) {
            throw new InvalidArgumentException("Invalid date. The date '{$point->format("Y-m-d")}' is too far in the future.");
        }

        return $date;
    }

    /**
     * Check inputted cbr.ru currency code format.
     *
     * @param string $currency Cbr.ru currency code. Example 'R01235' for USD
     * @return string Cbr.ru currency code. Example 'R01235' for USD
     * @throws BadFormatException Currency code unexpected format
     */
    protected static function checkInputCurrency(string $currency): string
    {
        if (str_starts_with($currency, 'R0') && strlen($currency) >= 6) {
            return $currency;
        }

        throw new BadFormatException('Currency code unexpected format.');
    }

    /**
     * Check tomorrow currency rate exist.
     * If date is tomorrow, than compare it with the last date from API response.
     * Cbr.ru could give as currency rate for tomorrow date.
     *
     * @param string $date     Requested date
     * @param string $lastDate Last record date from API response list
     * @return bool|null True - already has tomorrow's rate, false - not yet, null - date is not tomorrow
     */
    protected static function isTomorrowCurrencyRateExist(string $date, string $lastDate): ?bool
    {
        $lastDateObj = Carbon::parse($lastDate);
        $dateObj = Carbon::parse($date);
        if ($dateObj->isTomorrow()) {
            return $lastDateObj->eq($dateObj);
        }
        return null;
    }

    /**
     * Get currency rate from cbr.ru API by specified url.
     *
     * @param string $cbrUrl Cbr.ru API formatted url string
     * @return SimpleXMLElement List of currency rates by days
     * @throws ResponseException Empty API response
     * @throws XmlException XML format errors
     */
    private static function makeCbrApiRequest(string $cbrUrl): SimpleXMLElement
    {
        try {
            $response = simplexml_load_string(file_get_contents($cbrUrl));
        } catch (Exception $e) {
            $xmlError = libxml_get_last_error();
            if ($xmlError === false) {
                $xmlError = new LibXMLError;
            }
            throw new XmlException($e, $xmlError);
        }

        if (!isset($response->Record)) {
            throw new ResponseException('Empty API response.');
        }

        return $response;
    }

    /**
     * Checks if string has 'price' format.
     *
     * @param string $value Price value string
     * @return bool Value has 'price' format
     */
    protected static function isPriceFormat(string $value): bool
    {
        if (preg_match("/^[0-9]+\.[0-9]+$/", $value)) {
            return true;
        }

        return false;
    }

    /**
     * Get last record from list of currencies.
     *
     * @param SimpleXMLElement $response Response from cbr.ru API
     * @return array Array: (string)lastDate and (float)lastRate
     */
    // #[ArrayShape([
    //     'string', // lastDate 'Y-m-d'
    //     'float' // lastRate 5.1234
    // ])]
    protected static function getLastRecord(SimpleXMLElement $response): array
    {
        // Response has list of currencies on each date
        $records = [];
        foreach ($response->Record as $record) {
            $records[] = $record;
        }
        $lastRecord = array_pop($records); // Last value from central bank at that moment

        $lastDate = Carbon::parse((string)$lastRecord['Date'])->format('Y-m-d'); // To '2021-01-01' format
        $lastRate = (float)str_replace(',', '.', (string)$lastRecord->Value); // Currency format from russian to foreign

        return [$lastDate, $lastRate];
    }

    /**
     * Create cbr.ru API url for the specified period.
     *
     * @param Carbon $start        Start date of the range
     * @param Carbon $finish       Finish date of the range
     * @param string $currencyCode Cbr.ru currency code
     * @return string Cbr.ru API formatted string
     */
    protected static function createCbrApiUrl(Carbon $start, Carbon $finish, string $currencyCode): string
    {
        return "http://www.cbr.ru/scripts/XML_dynamic.asp?date_req1={$start->format('d/m/Y')}&date_req2={$finish->format('d/m/Y')}&VAL_NM_RQ={$currencyCode}";
    }
}
