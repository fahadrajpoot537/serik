<?php

namespace App\Support;

use Botble\Shortcode\Compilers\Shortcode;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned HTML fragment cache for anonymous homepage SSR sections.
 */
final class HomepageFragmentCache
{
  private const VERSION_PREFIX = 'homepage_fragment_version_v1:';

  private const CACHE_PREFIX = 'homepage_fragment_html_v1:';

  private const TTL_SECONDS = 3600;

  /** @var list<string> */
  public const CACHED_SHORTCODES = [
    'properties',
    'testimonials',
    'property-categories',
    'blog-posts',
    'services',
    'location',
  ];

  /** @var list<string> */
  private const PROPERTY_FRAGMENTS = [
    'properties',
    'property-categories',
    'location',
  ];

  private static ?bool $forceDisabled = null;

  public static function setDisabled(?bool $disabled): void
  {
    self::$forceDisabled = $disabled;
  }

  public static function isEnabled(): bool
  {
    if (self::$forceDisabled === true) {
      return false;
    }

    if (! SerikHomepage::isHomepageRequest()) {
      return false;
    }

    if (auth()->check()) {
      return false;
    }

    if (is_plugin_active('real-estate') && auth('account')->check()) {
      return false;
    }

    return true;
  }

  public static function shouldCacheShortcode(string $name): bool
  {
    return self::isEnabled() && in_array($name, self::CACHED_SHORTCODES, true);
  }

  public static function version(string $fragment): int
  {
    return (int) Cache::get(self::VERSION_PREFIX . $fragment, 1);
  }

  public static function bump(string $fragment): void
  {
    Cache::forever(self::VERSION_PREFIX . $fragment, self::version($fragment) + 1);
  }

  public static function bumpAll(): void
  {
    self::bump('header_menu');

    foreach (self::CACHED_SHORTCODES as $shortcode) {
      self::bump('shortcode:' . $shortcode);
    }
  }

  public static function bumpPropertyDependents(): void
  {
    self::bump('header_menu');

    foreach (self::PROPERTY_FRAGMENTS as $shortcode) {
      self::bump('shortcode:' . $shortcode);
    }
  }

  public static function rememberMenu(string $location, callable $render): string
  {
    $suffix = app()->getLocale() . ':' . $location;

    return self::remember('header_menu', $render, $suffix);
  }

  public static function rememberShortcode(string $name, Shortcode $compiled, callable $render): string
  {
    return self::remember('shortcode:' . $name, $render, self::shortcodeSuffix($name, $compiled));
  }

  public static function remember(string $fragment, callable $render, string $suffix = ''): string
  {
    if (! self::isEnabled()) {
      return self::stringify($render());
    }

    $version = self::version($fragment);
    $key = self::CACHE_PREFIX . $fragment . ':' . $version . ':' . $suffix;

    return Cache::remember($key, self::TTL_SECONDS, static fn (): string => self::stringify($render()));
  }

  public static function shortcodeSuffix(string $name, Shortcode $compiled): string
  {
    $attrs = $compiled->toArray();
    unset($attrs['enable_lazy_loading'], $attrs['enable_caching']);

    $parts = [app()->getLocale(), md5(serialize($attrs))];

    if ($name === 'properties') {
      $parts[] = self::visitorCityKey();
    }

    return implode(':', $parts);
  }

  private static function visitorCityKey(): string
  {
    try {
      if (class_exists(\Theme\homzen\Supports\VisitorCityHelper::class)) {
        $city = \Theme\homzen\Supports\VisitorCityHelper::get();

        if (is_string($city) && $city !== '') {
          return strtolower($city);
        }
      }
    } catch (\Throwable) {
      // ignore
    }

    return 'ontario';
  }

  private static function stringify(mixed $content): string
  {
    if ($content instanceof View) {
      return $content->render();
    }

    return (string) ($content ?? '');
  }

  public static function stringifyOutput(mixed $content): string
  {
    return self::stringify($content);
  }
}
