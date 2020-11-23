<?php declare(strict_types=1);

namespace steinmb\phpSerial;

final class InvalidSerialException extends \InvalidArgumentException
{
    public static function invalidBaudRate(array $validBaudRate, int $baudRate): InvalidSerialException
    {
        $values = '';
        foreach(array_keys($validBaudRate) as $rate) {
            $values .= $rate . ' ';
        }

        return new self(sprintf(
            'Invalid baud rate: "%d" Valid baud rates is: "%s"',
            $baudRate, $values
        ));
    }
}
