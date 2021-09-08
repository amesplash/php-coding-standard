<?php

declare(strict_types=1);

namespace Amesplash\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

use function ltrim;
use function strlen;
use function strrpos;

use const T_COMMENT;
use const T_DOC_COMMENT_STRING;
use const T_NS_SEPARATOR;
use const T_OPEN_TAG;
use const T_USE;
use const T_WHITESPACE;

/**
 * Checks the length of all lines in a file.
 *
 * Checks all lines in the file, and throws warnings if they are over 80
 * characters in length and errors if they are over 100. Both these
 * figures can be changed in a ruleset.xml file.
 */
class LineLengthSniff implements Sniff
{
    /**
     * The limit that the length of a line should not exceed.
     */
    public int $lineLimit = 80;

    /**
     * The limit that the length of a line must not exceed.
     *
     * Set to zero (0) to disable.
     *
     * @var int
     */
    public $absoluteLineLimit = 80;

    /**
     * Whether or not to ignore comment lines.
     *
     * @var bool
     */
    public $ignoreComments = false;

    /**
     * Whether or not to ignore namespaces lines.
     *
     * @var bool
     */
    public $ignoreUseStatementsLines = true;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return [T_OPEN_TAG];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in
     *                        the stack passed in $tokens.
     *
     * @return int
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = 1; $i < $phpcsFile->numTokens; $i++) {
            if ($tokens[$i]['column'] !== 1) {
                continue;
            }

            $this->checkLineLength($phpcsFile, $tokens, $i);
        }

        $this->checkLineLength($phpcsFile, $tokens, $i);

        // Ignore the rest of the file.
        return $phpcsFile->numTokens + 1;
    }

    /**
     * Checks if a line is too long.
     *
     * @param File  $phpcsFile The file being scanned.
     * @param array $tokens    The token stack.
     * @param int   $stackPtr  The first token on the next line.
     *
     * @return false|null
     */
    protected function checkLineLength($phpcsFile, $tokens, $stackPtr)
    {
        // The passed token is the first on the line.
        $stackPtr--;
        $firstToken = $tokens[$stackPtr];

        if ($firstToken['column'] === 1 && $firstToken['length'] === 0) {
            // Blank line.
            return;
        }

        if (
            $firstToken['column'] !== 1
            && $firstToken['content'] === $phpcsFile->eolChar
        ) {
            $stackPtr--;
        }

        $lineLength  = $firstToken['column'] + $firstToken['length'] - 1;
        $startOfLine = $stackPtr - $lineLength;

        if ($startOfLine <= 0) {
            $startOfLine = 0;
        }

        if (isset(Tokens::$phpcsCommentTokens[$firstToken['code']]) === true) {
            $prevNonWhiteSpace = $phpcsFile->findPrevious(
                T_WHITESPACE,
                $stackPtr - 1,
                $startOfLine,
                true
            );

            if ($firstToken['line'] !== $tokens[$prevNonWhiteSpace]['line']) {
                // Ignore PHPCS annotation comments if they are
                // on a line by themselves.
                return;
            }

            unset($prevNonWhiteSpace);
        }

        if ($this->ignoreUseStatementsLines === true) {
            $prevUseToken = $phpcsFile
                ->findPrevious(T_USE, $stackPtr - 1, $startOfLine);

            $prevNsSeparator = $phpcsFile
                ->findPrevious(T_NS_SEPARATOR, $stackPtr - 1, $startOfLine);

            if (
                $firstToken['line'] === $tokens[$prevUseToken]['line']
                && $firstToken['line'] === $tokens[$prevNsSeparator]['line']
            ) {
                return;
            }

            unset($prevUseToken);
            unset($prevNsSeparator);
        }

        // Record metrics for common line length groupings.
        if ($lineLength <= 80) {
            $phpcsFile->recordMetric($stackPtr, 'Line length', '80 or less');
        } elseif ($lineLength <= 120) {
            $phpcsFile->recordMetric($stackPtr, 'Line length', '81-120');
        } elseif ($lineLength <= 150) {
            $phpcsFile->recordMetric($stackPtr, 'Line length', '121-150');
        } else {
            $phpcsFile->recordMetric($stackPtr, 'Line length', '151 or more');
        }

        if (
            $firstToken['code'] === T_COMMENT
            || $firstToken['code'] === T_DOC_COMMENT_STRING
        ) {
            if ($this->ignoreComments === true) {
                return;
            }

            // If this is a long comment, check if it can be
            // broken up onto multiple lines.
            // Some comments contain unbreakable strings like
            //  URLs and so it makes sense
            // to ignore the line length in these cases if
            // the URL would be longer than the max
            // line length once you indent it to the correct level.
            if ($lineLength > $this->lineLimit) {
                $oldLength = strlen($firstToken['content']);
                $newLength = strlen(ltrim($firstToken['content'], "/#\t "));
                $indent = $firstToken['column'] - 1 + $oldLength - $newLength;
                $nonBreakingLength = $firstToken['length'];

                $space = strrpos($firstToken['content'], ' ');

                if ($space !== false) {
                    $nonBreakingLength -= $space + 1;
                }

                if ($nonBreakingLength + $indent > $this->lineLimit) {
                    return;
                }
            }
        }

        if (
            $this->absoluteLineLimit > 0
            && $lineLength > $this->absoluteLineLimit
        ) {
            $data = [
                $this->absoluteLineLimit,
                $lineLength,
            ];

            $error = 'Line exceeds limit of %s characters; has %s characters';
            $phpcsFile->addError($error, $stackPtr, 'MaxExceeded', $data);
        } elseif ($lineLength > $this->lineLimit) {
            $data = [
                $this->lineLimit,
                $lineLength,
            ];

            $warning = 'Line exceeds %s characters; has %s characters';
            $phpcsFile->addWarning($warning, $stackPtr, 'TooLong', $data);
        }
    }
}
