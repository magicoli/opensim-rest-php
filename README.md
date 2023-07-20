# OpenSimulator REST PHP library and command-line client

![Version 1.0.2](https://badgen.net/badge/Version/1.0.2/999999) ![Stable 1.0.2](https://badgen.net/badge/Stable/1.0.2/00aa00) ![Requires PHP 5.7](https://badgen.net/badge/PHP/5.7/7884bf) ![License AGPLv3](https://badgen.net/badge/License/AGPLv3/552b55)

This library can be used in another PHP project, or as a command-line client for OpenSimulator grids.

It allows to communicate with Robust instance started with -console rest arguments.

Available commands can be found here: <http://opensimulator.org/wiki/Server_Commands>

## Prerequisites

Remote connection must be enabled in your Robust .ini file.

**Do not leave default values!**. You should never need to type username and password manually, so you can safely [generate long random strings](https://www.random.org/strings/?num=2&len=32&digits=on&upperalpha=on&loweralpha=on&unique=on&format=plain&rnd=new).

You must choose a specific port, not already used by another service. It is good practice to limit access to this port to authorized IP addresses only in your firewall settings.

```ini
[Network]
  ConsoleUser = arandomgeneratedstring
  ConsolePass = anotherrandomgeneratedstring
  ConsolePort = 8009
  ; choose a port not already used by another service
```

## Command-line client

```bash
git clone https://github.com/magicoli/opensim-rest-php.git
sudo mv opensim-rest-php /opt/opensim-rest-php
sudo ln -s /opt/opensim-rest-php/opensim-rest-cli /usr/local/bin/opensim-rest-cli
```

You can run commands like

```bash
opensim-rest-cli /path/to/Robust.ini show info
opensim-rest-cli /path/to/Robust.ini show regions
```

If you save the credentials in ~/.opensim-rest-cli.ini, you can skip the Robust.ini argument.

```bash
opensim-rest-cli show info
opensim-rest-cli show regions
```

## PHP class

Only copy `class-rest.php` file in your project or install with composer. **Do not keep `opensim-rest-cli` or `opensim-rest-cli.php` in a production environment**.

```bash
composer require --dev https://github.com/magicoli/opensim-rest-php
cp magicoli/opensim-rest-php/class-rest.php lib/
```

In your project PHP:

```php
require(dirname(__FILE__) . '/lib/class-rest.php');

$session = opensim_rest_session(
  array(
    'uri' => "yourgrid.org:8009",
    'ConsoleUser' => 'yourConsoleUsername',
    'ConsolePass' => 'yourConolePassword',
  )
);

if ( is_opensim_rest_error($session) ) {
  error_log( "OpenSim_Rest error: " . $session->getMessage() );
} else {
  $responseLines = $session->sendCommand($command);
}

# Return value: an array containing the line(s) of response or a PHP Error
```
