<?php


interface ReceiveInterface
{
    public function __construct(SerialConnection $serialConnection);
    public function readPort();
}