<?php declare(strict_types=1);


namespace steinmb\phpSerial;


interface GatewayInterface
{
    public function __construct(
        SystemInterface $machine,
        ExecuteInterface $execute,
        CreatePort $portSettings
    );
    public function getDeviceStatus(): int;
    public function getDeviceHandle();
    public function changeDevice(string $deviceName);
    public function connect(string $mode);
    public function flush(): void;
    public function sendMessage(string $message): void;
}
