<?php
declare(strict_types=1);

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\LiveComponentInterface;

class PartComponent implements LiveComponentInterface
{
    /**
     * @LiveProp(writable=true)
     */
    public string $partNumber;

    public static function getComponentName(): string
    {
        return 'part';
    }
}
