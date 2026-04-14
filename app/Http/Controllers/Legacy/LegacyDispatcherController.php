<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

/**
 * Cake-like dispatcher:
 * - /{controller}/{action} -> App\Http\Controllers\Legacy\{Studly}Controller
 * - /admin/{controller}/{action} -> same convention for now
 *
 * This is intentionally conservative: it only supports 2-segment routes.
 * Expand later as more Cake route patterns are ported.
 */
class LegacyDispatcherController extends BaseController
{
    public function dispatch(Request $request, string $controller, string $action)
    {
        $controllerClass = $this->resolveControllerClass($controller, false, null);
        if ($controllerClass === null) {
            abort(404);
        }

        $instance = app()->make($controllerClass);
        $methodName = $this->resolveMethodName($instance, $action, null);
        if ($methodName === null) {
            abort(404);
        }

        return $this->invokeLegacyAction($instance, $methodName, $request, []);
    }

    public function dispatchAdmin(Request $request, string $controller, string $action)
    {
        $controllerClass = $this->resolveControllerClass($controller, true, null);
        if ($controllerClass === null) {
            abort(404);
        }

        $instance = app()->make($controllerClass);
        $methodName = $this->resolveMethodName($instance, $action, 'admin');
        if ($methodName === null) {
            abort(404);
        }

        return $this->invokeLegacyAction($instance, $methodName, $request, []);
    }

    public function dispatchWithPrefix(Request $request, string $prefix, string $controller, string $action)
    {
        $controllerClass = $this->resolveControllerClass($controller, false, strtolower($prefix));
        if ($controllerClass === null) {
            abort(404);
        }

        $instance = app()->make($controllerClass);
        $methodName = $this->resolveMethodName($instance, $action, strtolower($prefix));
        if ($methodName === null) {
            abort(404);
        }

        return $this->invokeLegacyAction($instance, $methodName, $request, []);
    }

    // Same as dispatch, but supports additional path params.
    public function dispatchWithPath(Request $request, string $controller, string $action, string $path)
    {
        $controllerClass = $this->resolveControllerClass($controller, false, null);
        if ($controllerClass === null) {
            abort(404);
        }

        $instance = app()->make($controllerClass);
        $methodName = $this->resolveMethodName($instance, $action, null);
        if ($methodName === null) {
            abort(404);
        }

        $routeParams = $this->parsePathParams($path);
        return $this->invokeLegacyAction($instance, $methodName, $request, $routeParams);
    }

    // Same as dispatchAdmin, but supports additional path params.
    public function dispatchAdminWithPath(Request $request, string $controller, string $action, string $path)
    {
        $controllerClass = $this->resolveControllerClass($controller, true, null);
        if ($controllerClass === null) {
            abort(404);
        }

        $instance = app()->make($controllerClass);
        $methodName = $this->resolveMethodName($instance, $action, 'admin');
        if ($methodName === null) {
            abort(404);
        }

        $routeParams = $this->parsePathParams($path);
        return $this->invokeLegacyAction($instance, $methodName, $request, $routeParams);
    }

    // Same as dispatchWithPrefix, but supports additional path params.
    public function dispatchWithPrefixAndPath(Request $request, string $prefix, string $controller, string $action, string $path)
    {
        $controllerClass = $this->resolveControllerClass($controller, false, strtolower($prefix));
        if ($controllerClass === null) {
            abort(404);
        }

        $instance = app()->make($controllerClass);
        $methodName = $this->resolveMethodName($instance, $action, strtolower($prefix));
        if ($methodName === null) {
            abort(404);
        }

        $routeParams = $this->parsePathParams($path);
        return $this->invokeLegacyAction($instance, $methodName, $request, $routeParams);
    }

    private function resolveControllerClass(string $controllerSegment, bool $adminNamespace, ?string $prefix): ?string
    {
        // Cake controller segments are often lowercase plural: logins -> LoginsController
        $studly = Str::studly($controllerSegment);

        $legacyClass = "\\App\\Http\\Controllers\\Legacy\\{$studly}Controller";
        $adminClass = "\\App\\Http\\Controllers\\Admin\\{$studly}Controller";
        $cloudClass = "\\App\\Http\\Controllers\\Cloud\\{$studly}Controller";

        // For `/admin/...` routes Cake still uses the same controllers,
        // but some actions we port first live under `Admin/`.
        // So we try `Admin/` first and fall back to `Legacy/`.
        if ($adminNamespace) {
            $candidates = [$adminClass, $legacyClass];
        } elseif ($prefix === 'cloud') {
            $candidates = [$cloudClass, $legacyClass];
        } else {
            $candidates = [$legacyClass];
        }

        foreach ($candidates as $fqcn) {
            if (class_exists($fqcn)) {
                return $fqcn;
            }
        }

        return null;
    }

    private function resolveMethodName(object $instance, string $action, ?string $prefix): ?string
    {
        if (method_exists($instance, $action)) {
            return $action;
        }

        // Cake-style underscored URLs (e.g. `sync_my_vehicle` -> `syncMyVehicle`).
        $camelAction = Str::camel($action);
        if ($camelAction !== $action && method_exists($instance, $camelAction)) {
            return $camelAction;
        }

        if ($prefix) {
            $prefixed = $prefix . '_' . $action;
            if (method_exists($instance, $prefixed)) {
                return $prefixed;
            }
        }

        // Cake also uses a few other prefixes in some controllers.
        // This fallback is conservative to avoid unexpected matches.
        if ($prefix !== null) {
            $otherPrefixed = 'cloud_' . $action;
            if ($prefix !== 'cloud' && method_exists($instance, $otherPrefixed)) {
                return $otherPrefixed;
            }
        }

        return null;
    }

    private function parsePathParams(string $path): array
    {
        $trimmed = trim($path);
        if ($trimmed === '' || $trimmed === '/') {
            return [];
        }

        return array_values(array_filter(explode('/', $trimmed), fn ($p) => $p !== ''));
    }

    private function invokeLegacyAction(object $instance, string $methodName, Request $request, array $routeParams): mixed
    {
        $refMethod = new \ReflectionMethod($instance, $methodName);

        $expectedParams = $refMethod->getParameters();
        if (count($expectedParams) === 0) {
            return $instance->{$methodName}();
        }

        $args = [];
        $routeIndex = 0;

        foreach ($expectedParams as $p) {
            $type = $p->getType();
            $isRequestTyped = $type && !$type->isBuiltin() && $type->getName() === Request::class;
            $isRequestByName = $p->getName() === 'request';

            if ($isRequestTyped || $isRequestByName) {
                $args[] = $request;
                continue;
            }

            $args[] = $routeParams[$routeIndex] ?? null;
            $routeIndex++;
        }

        return $instance->{$methodName}(...$args);
    }
}

