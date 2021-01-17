# CbrApiExchange

<p>Cbr.ru exchange API.</p>
<p>Gets currency rate from Russian Central Bank API, http://www.cbr.ru.</p>

<p>All currencies list: http://www.cbr.ru/scripts/XML_val.asp?d=0</p>
<p>Get USD rate example url: http://www.cbr.ru/scripts/XML_dynamic.asp?date_req1=01/01/2021&date_req2=12/01/2021&VAL_NM_RQ=R01235</p>

### Features
<p>Centrak Bank announce exchange rate for tomorrow everyday (in a second part of the day).</p>
<p>On holidays there are no announcements, so it use previous date of the exchange rate.</p>
<p>For example, on 2020-12-31 at 08:00 we don't get exchange rate for 2021-01-01, but at 21:00 we'll get it. And if we check exchange rate on 2021-01-04 we get it for 2021-01-01.</p>

## Install
```sh
$ composer require georgii-web/cbr-api-exchange
```

## How to
#### Add class
```
use CbrApiExchange\CbrApiExchange;
```

#### Use examples
```
CbrApiExchange::get(); // Get default currency 'R01235'(USD) on default date 'Today()'
CbrApiExchange::get('R01239'); // Get currency 'R01239'(EUR) on default date 'Today()'
CbrApiExchange::get('R01239', '2021-01-01'); // Get currency 'R01239'(EUR) on date '2021-01-01'
```

#### Returns
```
[
  "code" => "R01235", // The actual currency code
  "date" => "2021-01-01", // The actual date of the exchange rate
  "rate" => 73.8757, // Exchange rate of this currency to the Ruble
]
```

## Development

## Testing
```sh
$ ./scripts/test.sh
```

## Psalm check
```sh
$ ./scripts/psalm.sh
```