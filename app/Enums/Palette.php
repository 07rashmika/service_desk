<?php

namespace App\Enums;

/**
 * Named colour palettes for badges and status indicators.
 *
 * Statuses and priorities store one of these values in the database, so admins can
 * pick a colour without the app needing dynamically-built Tailwind class names.
 */
enum Palette: string
{
    case Slate = 'slate';
    case Blue = 'blue';
    case Violet = 'violet';
    case Amber = 'amber';
    case Orange = 'orange';
    case Emerald = 'emerald';
    case Red = 'red';
    case Primary = 'primary';

    /**
     * Resolve a stored palette name, falling back to slate for unknown values.
     */
    public static function fromName(self|string|null $name): self
    {
        if ($name instanceof self) {
            return $name;
        }

        return self::tryFrom((string) $name) ?? self::Slate;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Background, text and border classes for a tinted pill.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Slate => 'bg-slate-100 text-slate-600 border-slate-200',
            self::Blue => 'bg-blue-50 text-blue-700 border-blue-200',
            self::Violet => 'bg-violet-50 text-violet-700 border-violet-200',
            self::Amber => 'bg-amber-50 text-amber-700 border-amber-200',
            self::Orange => 'bg-orange-50 text-orange-700 border-orange-200',
            self::Emerald => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::Red => 'bg-red-50 text-red-700 border-red-200',
            self::Primary => 'bg-primary-50 text-primary-700 border-primary-200',
        };
    }

    /**
     * Solid background class for small indicator dots and colour swatches.
     */
    public function dotClass(): string
    {
        return match ($this) {
            self::Slate => 'bg-slate-400',
            self::Blue => 'bg-blue-500',
            self::Violet => 'bg-violet-500',
            self::Amber => 'bg-amber-500',
            self::Orange => 'bg-orange-500',
            self::Emerald => 'bg-emerald-500',
            self::Red => 'bg-red-500',
            self::Primary => 'bg-primary-500',
        };
    }
}
