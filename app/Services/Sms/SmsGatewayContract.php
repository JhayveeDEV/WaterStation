<?php

namespace App\Services\Sms;

/**
 * Any SMS provider plugs in by implementing this. IprogSmsGateway is the
 * default (see its doc comment for why it has a sandbox fallback).
 */
interface SmsGatewayContract
{
    /** Sends a plain SMS. Returns false on failure — callers should not treat this as fatal. */
    public function send(string $phoneNumber, string $message): bool;
}
