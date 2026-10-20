<?php

declare(strict_types=1);

use App\Db\Adapter;
use App\Mailer\FileTransport;
use DI\ContainerBuilder;
use Laminas\Db\Sql\Sql;
use Predis\Client;
use Predis\ClientInterface;
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
