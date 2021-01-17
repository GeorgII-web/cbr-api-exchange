<?php
/*
 * Copyright (c) 2021. GeorgII. george.webfullstack@gmail.com
 */

use CbrApiExchange\CbrApiExchange;
use PHPUnit\Framework\TestCase;

class ExchangeTest extends TestCase
{
    public function testExchangeResultCode()
    {
        $this->assertTrue(CbrApiExchange::get('R01235')['code'] === 'R01235');
    }

    public function testExchangeResultDate()
    {
        $this->assertTrue(CbrApiExchange::get('R01235')['date'] === '2021-01-16');
    }

    public function testExchangeResultCodeww()
    {

        $res = CbrApiExchange::get('R01235');
        // CbrApiExchange::get('R01239'),
        // CbrApiExchange::get('R01235', $date),
        self::assertSame($res['code'], 'R01235');
        // $this->expectException(InvalidArgumentException::class);
    }
}
