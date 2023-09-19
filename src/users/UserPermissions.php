<?php
/*
 * Copyright (c) 2023. Hadrien Sevel
 * Project: forum-rest-api
 * File: UserPermissions.php
 */

namespace Users;

class UserPermissions
{
    public const TEACHER = 'teacher';
    public const ASSISTANT = 'assistant';
    public const STUDENT = 'student';

    public static function canEditQuestion(string $userRole, bool $isAdmin, bool $isAuthor = false): bool
    {
        return $isAdmin || $userRole === self::TEACHER || $userRole === self::ASSISTANT || $isAuthor;
    }

    public static function canDeleteQuestion(string $userRole, bool $isAdmin, bool $isAuthor = false): bool
    {
        return $isAdmin || $userRole === self::TEACHER || $userRole === self::ASSISTANT;
    }

    public static function canLockQuestion(string $userRole, bool $isAdmin): bool
    {
        return $isAdmin || $userRole === self::TEACHER;
    }

    public static function canEditAnswer(string $userRole, bool $isAdmin, bool $isAuthor = false): bool
    {
        return $isAdmin || $userRole === self::TEACHER || $userRole === self::ASSISTANT || $isAuthor;
    }

    public static function canDeleteAnswer(string $userRole, bool $isAdmin, bool $isAuthor = false): bool
    {
        return $isAdmin || $userRole === self::TEACHER || $userRole === self::ASSISTANT;
    }

    public static function canAcceptAnswer(string $userRole, bool $isAdmin): bool
    {
        return $isAdmin || $userRole === self::TEACHER || $userRole === self::ASSISTANT;
    }
}