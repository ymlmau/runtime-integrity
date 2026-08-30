<?php
namespace YmlMau\RuntimeIntegrity\Transport;

interface TransportInterface
{
    public function send(array $event);
    public function getName();
}
