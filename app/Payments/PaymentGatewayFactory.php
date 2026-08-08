<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    public function __construct(private Container $container)
    {
    }

    public function make(string $routeSlug): PaymentGateway
    {
        $config = $this->configForRoute($routeSlug);

        return $this->container->make($config['driver'], ['config' => $config]);
    }

    /**
     * URL segments accepted by the /payment/{gateway}/... routes.
     */
    public function routeSlugs(): array
    {
        return array_column(config('payment.gateways'), 'route');
    }

    private function configForRoute(string $routeSlug): array
    {
        foreach (config('payment.gateways') as $config) {
            if ($config['route'] === $routeSlug) {
                return $config;
            }
        }

        throw new InvalidArgumentException("Unknown payment gateway route [{$routeSlug}].");
    }
}
