<?php

use CbrApiExchange\CbrApiExchange;

class ExchangeResultTest
{

    /**
     * Check if exchange result is right
     *
     * @param $code
     * @param $date
     * @param $resDate
     * @param $rate
     * @return bool
     */
    public function assertExchange($code, $date, $resDate, $rate): bool
    {
        try {
            $res = CbrApiExchange::get($code, $date);
        } catch (Exception) {
            $res = [];
        }

        return $res === [
                'code' => $code,
                'date' => $resDate,
                'rate' => $rate,
            ];
    }
}


test('USD on 2021-01-01 result', function () {
    expect((new ExchangeResultTest())->assertExchange('R01235', '2021-01-01', '2021-01-01', 73.8757))->toBeTrue();
});

test('USD on 2021-01-04 result', function () {
    expect((new ExchangeResultTest())->assertExchange('R01235', '2021-01-04', '2021-01-01', 73.8757))->toBeTrue();
});

test('USD on 2021-01-16 result', function () {
    expect((new ExchangeResultTest())->assertExchange('R01235', '2021-01-16', '2021-01-16', 73.5453))->toBeTrue();
});

test('EUR on 2021-01-16 result', function () {
    expect((new ExchangeResultTest())->assertExchange('R01239', '2021-01-16', '2021-01-16', 89.2546))->toBeTrue();
});

test('GBP on 2021-01-16 result', function () {
    expect((new ExchangeResultTest())->assertExchange('R01035', '2021-01-16', '2021-01-16', 100.3599))->toBeTrue();
});

test('USD on tomorrow exchange', function () {
    $code = 'R01235';
    $date = '2021-01-19';

    try {
        //tomorrow exchange announced
        $res = CbrApiExchange::get($code, $date);
        $resException = null;
    } catch (Exception $e) {
        //not yet
        $resException = $e->getMessage();
    }

    if ($resException) {
        expect($resException)->toBe('The exchange rate for tomorrow has not yet been announced');
    } else {
        expect($res['code'] === $code && $res['date'] === $date)->toBeTrue();
    }
});

test('USD on 2111-01-01 exception', function () {
    CbrApiExchange::get('R01235', '2111-01-01');
})->throws(Exception::class, "Invalid date. The date '2111-01-01' is too far in the future");

test('Undefined currency exception', function () {
    CbrApiExchange::get('R00000', '2021-01-01');
})->throws(Exception::class, "Empty API response");