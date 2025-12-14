<?php


namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SUCCESSFUL => 'Successful',
            self::FAILED => 'Failed',
        };
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::SUCCESSFUL, self::FAILED]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
