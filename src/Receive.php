<?php


class Receive implements ReceiveInterface
{
    private $serialConnection;

    public function __construct(SerialConnection $serialConnection)
    {
        $this->serialConnection = $serialConnection;
    }

    /**
     * Reads the port until no new datas are availible, then return the content.
     *
     * @param int $count Number of characters to be read (will stop before
     *                   if less characters are in the buffer)
     * @return string
     */
    public function readPort($count = 0)
    {
        if ($this->serialConnection->_dState !== SERIAL_DEVICE_OPENED) {
            trigger_error("Device must be opened to read it", E_USER_WARNING);
            return false;
        }

        if ($this->serialConnection->_os === "linux" || $this->serialConnection->_os === "osx") {
            // Behavior in OSX isn't to wait for new data to recover, but just
            // grabs what's there!
            // Doesn't always work perfectly for me in OSX
            $content = ""; $i = 0;

            if ($count !== 0) {
                do {
                    if ($i > $count) {
                        $content .= fread($this->serialConnection->_dHandle, ($count - $i));
                    } else {
                        $content .= fread($this->serialConnection->_dHandle, 128);
                    }
                } while (($i += 128) === strlen($content));
            } else {
                do {
                    $content .= fread($this->serialConnection->_dHandle, 128);
                } while (($i += 128) === strlen($content));
            }

            return $content;
        }

        if ($this->serialConnection->_os === "windows") {
            // Windows port reading procedures still buggy
            $content = ""; $i = 0;

            if ($count !== 0) {
                do {
                    if ($i > $count) {
                        $content .= fread($this->serialConnection->_dHandle, ($count - $i));
                    } else {
                        $content .= fread($this->serialConnection->_dHandle, 128);
                    }
                } while (($i += 128) === strlen($content));
            } else {
                do {
                    $content .= fread($this->serialConnection->_dHandle, 128);
                } while (($i += 128) === strlen($content));
            }

            return $content;
        }

        return false;
    }
}