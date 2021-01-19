<?php
/*
 * Copyright (c) 2021. GeorgII. george.webfullstack@gmail.com
 */

namespace CbrApiExchange\Exceptions;

use Exception;
use JetBrains\PhpStorm\Pure;
use LibXMLError;

/**
 * Error reading XML file
 */
class XmlException extends Exception
{
    #[Pure] public function __construct(private Exception $exception, private LibXMLError $xmlError)
    {
        parent::__construct($exception->message, $exception->code);
    }

    public function getLibXmlError(): LibXMLError
    {
        return $this->xmlError;
    }
}