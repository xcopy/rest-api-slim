<?php

declare(strict_types=1);

use App\Db\Adapter;
use App\Mailer\FileTransport;
use DI\ContainerBuilder;
use Laminas\Db\Sql\Sql;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Predis\Client;
use Predis\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;

$definitions = [
    Adapter::class => fn() => new Adapter(config('db')),
    ClientInterface::class => function () {
        $config = config('redis');
        $parameters = [
            'scheme' => 'tcp',
            'host' => $config['host'],
            'port' => $config['port'],
            'timeout' => $config['timeout'],
            'read_write_timeout' => $config['timeout'],
            'database' => $config['database'],
        ];

        $username = $config['username'];
        if ($username !== null && $username !== '') {
            $parameters['username'] = $username;
        }

        $password = $config['password'];
        if ($password !== null && $password !== '') {
            $parameters['password'] = $password;
        }

        return new Client($parameters, [
            'prefix' => $config['prefix'],
        ]);
    },
    LoggerInterface::class => function () {
        $stream = new RotatingFileHandler(
            APP_BASE_PATH . '/logs/app.log',
            maxFiles: 14, // two weeks
            level: config('app.env') === 'production' ? Level::Warning : Level::Debug
        );

        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            DATE_FORMAT_LOG,
            allowInlineLineBreaks: true,
            ignoreEmptyContextAndExtra: true
        );

        $stream->setFormatter($formatter);

        $logger = new Logger('app');
        $logger->pushHandler($stream);

        return $logger;
    },
    MailerInterface::class => function () {
        $transport = in_array(config('app.env'), ['local', 'testing'])
            ? new FileTransport(APP_BASE_PATH . '/logs/mail')
            : Transport::fromDsn(config('mail.dsn'));

        return new Mailer($transport);
    },
    Sql::class => fn(Adapter $db) => new Sql($db),
];

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAttributes(true);
$containerBuilder->addDefinitions($definitions);

return $containerBuilder->build();
