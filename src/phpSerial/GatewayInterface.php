<?php declare(strict_types=1);


namespace steinmb\phpSerial;


interface GatewayInterface
{
    public function __construct(
        System $machine,
        string $device,
        int $baudRate,
        string $parity,
        int $characterLength,
        float $stopBits,
        string $flowControl
    );
    public function getDeviceStatus(): int;
    public function getDeviceHandle();
    public function changeDevice(string $deviceName);
    public function connect(string $mode);
    public function flush(): void;
    public function sendMessage(string $message): void;
}
