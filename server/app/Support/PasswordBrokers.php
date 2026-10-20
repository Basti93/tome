<?php

namespace App\Support;

use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;
use LogicException;

class PasswordBrokers
{
    /**
     * The concrete broker (token create/delete are not part of the PasswordBroker contract).
     */
    public static function get(string $name): PasswordBroker
    {
        $broker = Password::broker($name);
        if (! $broker instanceof PasswordBroker) {
            throw new LogicException("Password broker [{$name}] does not support token management.");
        }

        return $broker;
    }
}
