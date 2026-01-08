<?php

namespace App\Enums;

enum EmailTemplateType: string
{
    case REGISTRATION_CONFIRMATION = 'registration_confirmation';
    case ATTENDANCE_CONFIRMATION = 'attendance_confirmation';
    case FINAL_PREPARATION = 'final_preparation';

    /**
     * Get the human-readable label for the enum case.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::REGISTRATION_CONFIRMATION => 'Registration Confirmation',
            self::ATTENDANCE_CONFIRMATION => 'Attendance Confirmation',
            self::FINAL_PREPARATION => 'Final Preparation',
        };
    }

    /**
     * Get the description for the enum case.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::REGISTRATION_CONFIRMATION => 'Sent immediately after invoice & pre-training form',
            self::ATTENDANCE_CONFIRMATION => 'Sent 2 weeks before training',
            self::FINAL_PREPARATION => 'Sent 1 week before training',
        };
    }
}
