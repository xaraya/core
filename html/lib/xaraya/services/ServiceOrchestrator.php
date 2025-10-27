<?php

/**
 * Core Services for classes (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Services;

use sys;

sys::import('xaraya.services.servicefactory');
sys::import('xaraya.services.staticservicesclass');

/**
 * The ServiceOrchestrator is responsible for the entire lifecycle of service
 * resolution, including caching and specialization.
 */
class ServiceOrchestrator
{
    /**
     * Get a service instance for a given parent, handling all caching and specialization.
     *
     * @param CoreServicesInterface $parent The object requesting the service.
     * @param string $name The name of the service.
     * @param mixed ...$args Arguments for service specialization.
     * @return ServiceInterface The requested service instance.
     */
    public static function get(CoreServicesInterface $parent, string $name, ...$args): ServiceInterface
    {
        // Use a unique key for services with arguments (e.g., mod('roles'), user(123))
        $cacheKey = $name;
        if (!empty($args)) {
            // Simple key generation, assuming scalar arguments.
            $cacheKey .= '-' . implode('.', $args);
        }

        // Check for a locally cached or mocked service first.
        // We need to access the parent's local cache.
        if ($parent->hasLocalService($cacheKey)) {
            return $parent->getLocalService($cacheKey);
        }

        // Get the single StaticServicesClass instance for this request.
        $services = $parent->getStaticServices();

        // 1. Handle shared services (request-scoped singletons).
        if (in_array($name, ServiceFactory::$sharedServices)) {
            // Shared services are singletons per request, so we return the prototype directly.
            return $services->getServicePrototype($name);
        }

        // 2. Handle argument-aware services (cached centrally per argument set).
        if (in_array($name, ServiceFactory::$argumentServices)) {
            // If already cached centrally (e.g., user-123), return it.
            if (isset($services->serviceCache[$cacheKey])) {
                return $services->serviceCache[$cacheKey];
            }

            // Get the base prototype (e.g., 'user').
            $prototype = $services->getServicePrototype($name);

            // Tell the prototype to create a specialized version of itself.
            $serviceInstance = $prototype->specialize(...$args);

            // Cache the specialized instance centrally for the whole request and return it.
            return $services->serviceCache[$cacheKey] = $serviceInstance;
        }

        // 3. Handle parent-aware services (cloned and cached locally per parent).

        // Ensure the generic parent-aware service is created and cached locally first.
        if (!$parent->hasLocalService($name)) {
            $prototype = $services->getServicePrototype($name);
            $serviceInstance = clone $prototype;
            if (method_exists($serviceInstance, 'setParent')) {
                $serviceInstance->setParent($parent);
            }
            $parent->setLocalService($name, $serviceInstance);
        }

        // If no arguments were passed, we're done. Return the generic local service.
        if (empty($args)) {
            return $parent->getLocalService($name);
        }

        // For requests with arguments, clone the *local generic* service and specialize it.
        $prototype = $parent->getLocalService($name);
        $specializedInstance = $prototype->specialize(...$args);

        // Cache and return the specialized local instance.
        $parent->setLocalService($cacheKey, $specializedInstance);
        return $specializedInstance;
    }
}
