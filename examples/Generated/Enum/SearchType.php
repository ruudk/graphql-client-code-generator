<?php

declare(strict_types=1);

namespace Ruudk\GraphQLCodeGenerator\Examples\Generated\Enum;

// This file was automatically generated and should not be edited.

/**
 * @api
 */
enum SearchType: string
{
    // Returns matching discussions in repositories.
    case Discussion = 'DISCUSSION';

    // Returns results matching issues in repositories.
    case Issue = 'ISSUE';

    // Returns results matching issues in repositories.
    case IssueAdvanced = 'ISSUE_ADVANCED';

    // Returns results matching repositories.
    case Repository = 'REPOSITORY';

    // Returns results matching users and organizations on GitHub.
    case User = 'USER';

    public function isDiscussion() : bool
    {
        return $this === self::Discussion;
    }

    public static function createDiscussion() : self
    {
        return self::Discussion;
    }

    public function isIssue() : bool
    {
        return $this === self::Issue;
    }

    public static function createIssue() : self
    {
        return self::Issue;
    }

    public function isIssueAdvanced() : bool
    {
        return $this === self::IssueAdvanced;
    }

    public static function createIssueAdvanced() : self
    {
        return self::IssueAdvanced;
    }

    public function isRepository() : bool
    {
        return $this === self::Repository;
    }

    public static function createRepository() : self
    {
        return self::Repository;
    }

    public function isUser() : bool
    {
        return $this === self::User;
    }

    public static function createUser() : self
    {
        return self::User;
    }
}
