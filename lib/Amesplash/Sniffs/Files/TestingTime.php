<?php

declare(strict_types=1);

namespace Amesplash\Sniffs\Files;

class TestingTime implements Sniff
{
    private function name(string $name): string
    {
        switch ($name) {
            case 'value':
                $named = 'Evolving Timu';
                break;

            default:
                $named = 'Morphing timu';

                break;
        }

        return $named;
    }
}