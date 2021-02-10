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
    // #[Pure]
    private Exception $exception;
    private LibXMLError $xmlError;

    public function __construct(Exception $exception, LibXMLError $xmlError)
    {
        $this->xmlError = $xmlError;
        $this->exception = $exception;
        parent::__construct($exception->message, $exception->code);
    }

    public function getLibXmlError(): LibXMLError
    {
        return $this->xmlError;
    }
}
