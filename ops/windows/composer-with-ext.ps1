param(
    [Parameter(Mandatory = $false)]
    [string]$Command = 'install',
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$ComposerArgs
)

$php = 'C:\xampp\php\php.exe'
$composer = 'C:\ProgramData\ComposerSetup\bin\composer.phar'
$extDir = 'C:\xampp\php\ext'
$extensions = @(
    'curl', 'openssl', 'mbstring', 'sockets', 'zip', 'pdo_mysql',
    'fileinfo', 'gd', 'sodium', 'intl', 'exif'
)

# Prefer normal php.ini, but disable CLI opcache (XAMPP ZTS access-violation crashes).
$phpArgs = @(
    '-d', 'opcache.enable_cli=0',
    '-d', 'memory_limit=1G'
)
$phpArgs += $composer
$phpArgs += '--ignore-platform-req=ext-sockets'
if ($Command -and $Command.Trim() -ne '') {
    $phpArgs += $Command
}
if ($ComposerArgs) {
    $phpArgs += $ComposerArgs
}

& $php @phpArgs
exit $LASTEXITCODE
