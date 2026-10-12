<?php

namespace Tests\Feature\Security;

use App\Enums\TicketState;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesTicketUsers;
use Tests\TestCase;

/**
 * Walks through every route, so a new page or endpoint can't be added without
 * sign-in or permission checks by accident.
 */
class RouteProtectionTest extends TestCase
{
    use CreatesTicketUsers, RefreshDatabase;

    /**
     * Routes anyone may open without signing in.
     *
     * @var array<int, string>
     */
    protected const PUBLIC_URIS = [
        '/',
        'up',
        'login',
        'forgot-password',
        'reset-password',
        'reset-password/{token}',
        'sanctum/csrf-cookie',
        'styleguide',
        'api/v1/auth/token',
        '{fallbackPlaceholder}',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTicketReferenceData();
    }

    /**
     * @return array<int, array{method: string, route: Route}>
     */
    protected function routesToCheck(): array
    {
        return collect(Router::getRoutes()->getRoutes())
            ->reject(fn (Route $route): bool => in_array($route->uri(), self::PUBLIC_URIS, true)
                || Str::startsWith($route->uri(), ['_boost', 'broadcasting']))
            ->map(fn (Route $route): array => ['method' => collect($route->methods())->reject(fn (string $method): bool => $method === 'HEAD')->first(), 'route' => $route])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, int|string>  $parameters
     */
    protected function urlFor(Route $route, array $parameters = []): string
    {
        return url(preg_replace_callback('/\{(\w+)\??\}/', fn (array $match): string => (string) ($parameters[$match[1]] ?? 1), $route->uri()));
    }

    public function test_every_private_route_requires_signing_in(): void
    {
        $routes = $this->routesToCheck();
        $this->assertGreaterThan(50, count($routes));

        foreach ($routes as ['method' => $method, 'route' => $route]) {
            $isApi = str_starts_with($route->uri(), 'api/');
            $response = $isApi ? $this->json($method, $this->urlFor($route)) : $this->call($method, $this->urlFor($route));

            if ($isApi) {
                $this->assertSame(401, $response->status(), "{$method} /{$route->uri()} should need an API token.");
            } else {
                $this->assertTrue($response->isRedirect(route('login')), "{$method} /{$route->uri()} should redirect guests to the login page, got {$response->status()}.");
            }
        }
    }

    public function test_employees_are_refused_on_every_support_and_admin_route(): void
    {
        $employee = $this->employee();
        $parameters = [
            'ticket' => Ticket::factory()->inState(TicketState::Open)->create()->id,
            'user' => $this->supportAgent()->id,
            'department' => Department::factory()->create()->id,
            'category' => TicketCategory::factory()->create()->id,
            'priority' => TicketPriority::factory()->create()->id,
            'status' => TicketStatus::query()->firstOrFail()->id,
            'role' => 'support',
        ];

        $routes = collect($this->routesToCheck())
            ->filter(fn (array $item): bool => Str::startsWith($item['route']->uri(), ['admin/', 'support/']));
        $this->assertGreaterThan(30, $routes->count());

        foreach ($routes as ['method' => $method, 'route' => $route]) {
            $response = $this->actingAs($employee)->call($method, $this->urlFor($route, $parameters));

            $this->assertSame(403, $response->status(), "An employee should not be able to use {$method} /{$route->uri()}.");
        }
    }
}
