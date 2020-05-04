<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Requirements\Requirement;
use Symfony\Requirements\SymfonyRequirements;

if (file_exists($autoloader = __DIR__.'/../../../autoload.php')) {
    require_once $autoloader;
} elseif (file_exists($autoloader = __DIR__.'/../vendor/autoload.php')) {
    require_once $autoloader;
} else {
    throw new \RuntimeException('Unable to find the Composer autoloader.');
}

$lineSize = 70;
$isVerbose = in_array('-v', $argv) || in_array('-vv', $argv) || in_array('-vvv', $argv);

$symfonyVersion = class_exists('\Symfony\Component\HttpKernel\Kernel') ? \Symfony\Component\HttpKernel\Kernel::VERSION : null;

$symfonyRequirements = new SymfonyRequirements(dirname(dirname(realpath($autoloader))), $symfonyVersion);
$iniPath = $symfonyRequirements->getPhpIniPath();

echo_title('Symfony Requirements Checker');

echo '> PHP is using the following php.ini file:'.PHP_EOL;
if ($iniPath) {
    echo_style('green', $iniPath);
} else {
    echo_style('yellow', 'WARNING: No configuration file (php.ini) used by PHP!');
}

echo PHP_EOL.PHP_EOL;

echo '> Checking Symfony requirements:'.PHP_EOL;

$messages = array();
foreach ($symfonyRequirements->getRequirements() as $req) {
    if ($helpText = get_error_message($req, $lineSize)) {
        if ($isVerbose) {
            echo_style('red', '[ERROR] ');
            echo $req->getTestMessage().PHP_EOL;
        } else {
            echo_style('red', 'E');
        }

        $messages['error'][] = $helpText;
    } else {
        if ($isVerbose) {
            echo_style('green', '[OK] ');
            echo $req->getTestMessage().PHP_EOL;
        } else {
            echo_style('green', '.');
        }
    }
}

$checkPassed = empty($messages['error']);

foreach ($symfonyRequirements->getRecommendations() as $req) {
    if ($helpText = get_error_message($req, $lineSize)) {
        if ($isVerbose) {
            echo_style('yellow', '[WARN] ');
            echo $req->getTestMessage().PHP_EOL;
        } else {
            echo_style('yellow', 'W');
        }

        $messages['warning'][] = $helpText;
    } else {
        if ($isVerbose) {
            echo_style('green', '[OK] ');
            echo $req->getTestMessage().PHP_EOL;
        } else {
            echo_style('green', '.');
        }
    }
}

if ($checkPassed) {
    echo_block('success', 'OK', 'Your system is ready to run Symfony projects');
} else {
    echo_block('error', 'ERROR', 'Your system is not ready to run Symfony projects');

    echo_title('Fix the following mandatory requirements', 'red');

    foreach ($messages['error'] as $helpText) {
        echo ' * '.$helpText.PHP_EOL;
    }
}

if (!empty($messages['warning'])) {
    echo_title('Optional recommendations to improve your setup', 'yellow');

    foreach ($messages['warning'] as $helpText) {
        echo ' * '.$helpText.PHP_EOL;
    }
}

echo PHP_EOL;
echo_style('title', 'Note');
echo '  The command console could use a different php.ini file'.PHP_EOL;
echo_style('title', '~~~~');
echo '  than the one used with your web server. To be on the'.PHP_EOL;
echo '      safe side, please check the requirements from your web'.PHP_EOL;
echo '      server using the ';
echo_style('yellow', 'public/check.php');
echo ' script.'.PHP_EOL;
echo PHP_EOL;

exit($checkPassed ? 0 : 1);

function get_error_message(Requirement $requirement, $lineSize)
{
    if ($requirement->isFulfilled()) {
        return;
    }

    $errorMessage = wordwrap($requirement->getTestMessage(), $lineSize - 3, PHP_EOL.'   ').PHP_EOL;
    $errorMessage .= '   > '.wordwrap($requirement->getHelpText(), $lineSize - 5, PHP_EOL.'   > ').PHP_EOL;

    return $errorMessage;
}

function echo_title($title, $style = null)
{
    $style = $style ?: 'title';

    echo PHP_EOL;
    echo_style($style, $title.PHP_EOL);
    echo_style($style, str_repeat('~', strlen($title)).PHP_EOL);
    echo PHP_EOL;
}

function echo_style($style, $message)
{
    // ANSI color codes
    $styles = array(
        'reset' => "\033[0m",
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'error' => "\033[37;41m",
        'success' => "\033[37;42m",
        'title' => "\033[34m",
    );
    $supports = has_color_support();

    echo($supports ? $styles[$style] : '').$message.($supports ? $styles['reset'] : '');
}

function echo_block($style, $title, $message)
{
    $message = ' '.trim($message).' ';
    $width = strlen($message);

    echo PHP_EOL.PHP_EOL;

    echo_style($style, str_repeat(' ', $width));
    echo PHP_EOL;
    echo_style($style, str_pad(' ['.$title.']', $width, ' ', STR_PAD_RIGHT));
    echo PHP_EOL;
    echo_style($style, $message);
    echo PHP_EOL;
    echo_style($style, str_repeat(' ', $width));
    echo PHP_EOL;
}

function has_color_support()
{
    static $support;

    if (null === $support) {
        if (DIRECTORY_SEPARATOR == '\\') {
            $support = false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI');
        } else {
            $support = function_exists('posix_isatty') && @posix_isatty(STDOUT);
        }
    }

    return $support;
}
