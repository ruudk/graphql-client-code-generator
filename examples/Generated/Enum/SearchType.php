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

    // Returns results matching issues using hybrid (lexical + semantic) search.
    case IssueHybrid = 'ISSUE_HYBRID';

    // Returns results matching issues using semantic search.
    case IssueSemantic = 'ISSUE_SEMANTIC';

    // Returns results matching repositories.
    case Repository = 'REPOSITORY';

    // Returns results matching users and organizations on GitHub.
    case User = 'USER';
}
