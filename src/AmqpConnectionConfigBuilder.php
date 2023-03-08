<?php

namespace Anik\Amqp;

use PhpAmqpLib\Connection\AMQPConnectionConfig;

class AmqpConnectionConfigBuilder implements ConfigBuilder
{
    protected $mappers = [
        'host' => 'setHost',
        'port' => 'setPort',
        'user' => 'setUser',
        'password' => 'setPassword',
        'vhost' => 'setVhost',
        'io' => 'setIoType',
        'secure' => 'setIsSecure',
        'lazy' => 'setIsLazy',
        'insist' => 'setInsist',
        'login_method' => 'setLoginMethod',
        'login_response' => 'setLoginResponse',
        'locale' => 'setLocale',
        'connection_timeout' => 'setConnectionTimeout',
        'read_timeout' => 'setReadTimeout',
        'write_timeout' => 'setWriteTimeout',
        'keepalive' => 'setKeepalive',
        'heartbeat' => 'setHeartbeat',
        'channel_rpc_timeout' => 'setChannelRPCTimeout',
        'network_protocol' => 'setNetworkProtocol',
        'stream_context' => 'setStreamContext',
        'send_buffer_size' => 'setSendBufferSize',
        'signal_dispatch' => 'enableSignalDispatch',
        'amqp_protocol' => 'setAMQPProtocol',
        'protocol_strict_fields' => 'setProtocolStrictFields',
        'cafile' => 'setSslCaCert',
        'capath' => 'setSslCaPath',
        'local_cert' => 'setSslCert',
        'local_pk' => 'setSslKey',
        'verify_peer' => 'setSslVerify',
        'verify_peer_name' => 'setSslVerifyName',
        'passphrase' => 'setSslPassPhrase',
        'ciphers' => 'setSslCiphers',
        'debug_packets' => 'setDebugPackets',
        'connection_name' => 'setConnectionName',
    ];

    protected $defaults = [
        'vhost' => '/',
        'io' => AMQPConnectionConfig::IO_TYPE_SOCKET,
        'secure' => false,
        'lazy' => true,
    ];

    protected function getMappers(): array
    {
        return $this->mappers;
    }

    protected function getDefaults(): array
    {
        return $this->defaults;
    }

    public function build(array $options): AMQPConnectionConfig
    {
        $config = new AMQPConnectionConfig();

        $mappers = $this->getMappers();
        $defaults = $this->getDefaults();

        /** Add defaults to options array if the keys don't exist */
        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $options)) {
                $options[$key] = $value;
            }
        }

        /** Apply options to configuration */
        foreach ($options as $key => $value) {
            if (!array_key_exists($key, $mappers)) {
                continue;
            }

            $config->{$mappers[$key]}($value);
        }

        return $config;
    }
}
