<?php
// Barebones caching config variables - 
// The idea is to keep the overhead low when doing page
// level caching.

// Flag files to enable caching:
// sys::varpath() . '/cache/output/cache.touch' : activate output caching in general
// sys::varpath() . '/cache/output/cache.pagelevel' : enable page caching
// sys::varpath() . '/cache/output/cache.blocklevel' : enable block caching
// sys::varpath() . '/cache/output/cache.modulelevel' : enable module caching
// sys::varpath() . '/cache/output/cache.objectlevel' : enable object caching

// Only cache this theme
$cachingConfiguration['Output.DefaultTheme'] = 'default';
// Size in bytes to limit the output cache to
$cachingConfiguration['Output.SizeLimit'] = 2097152;
// Default site cookie name 
$cachingConfiguration['Output.CookieName'] = 'XARAYASID';
// Default site locale
$cachingConfiguration['Output.DefaultLocale'] = 'en_US.utf-8';

// Cached page expiration time in seconds
$cachingConfiguration['Page.TimeExpiration'] = 1800;
// Should we cache display view, or not
$cachingConfiguration['Page.DisplayView'] = 0;
// Should we show when the cached page was created or not
$cachingConfiguration['Page.ShowTime'] = 1;
// Send an "Expires: $time" header to the client
$cachingConfiguration['Page.ExpireHeader'] = 0;
// Allow page caching for the following user groups (besides anonymous)
$cachingConfiguration['Page.CacheGroups'] = '';
// Only cache the pages of modules hooked to xarCacheManager
$cachingConfiguration['Page.HookedOnly'] = 0;
// Store the cached pages in filesystem, database or memcache
$cachingConfiguration['Page.CacheStorage'] = 'filesystem';
// Keep a logfile of the cache hits and misses for pages, e.g. in var/logs/page.log
$cachingConfiguration['Page.LogFile'] = '';
// Size in bytes to limit the page cache to
$cachingConfiguration['Page.SizeLimit'] = 2097152;

// Allow session-less page caching of these URLs for first-time visitors
$cachingConfiguration['Page.SessionLess'] = array();

// Period of analysis for the auto-cache
$cachingConfiguration['AutoCache.Period'] = 0;
// Threshold for inclusion in the auto-cache
$cachingConfiguration['AutoCache.Threshold'] = 10;
// Maximum number of pages in the auto-cache
$cachingConfiguration['AutoCache.MaxPages'] = 25;
// Pages to include in the auto-cache
$cachingConfiguration['AutoCache.Include'] = array();
// Pages to exclude from the auto-cache
$cachingConfiguration['AutoCache.Exclude'] = array();
// Keep historic data for cache statistics
$cachingConfiguration['AutoCache.KeepStats'] = 0;

// Maximum life time of block cache in seconds
$cachingConfiguration['Block.TimeExpiration'] = 7200;
// Store the cached blocks in filesystem, database or memcache
$cachingConfiguration['Block.CacheStorage'] = 'filesystem';
// Keep a logfile of the cache hits and misses for blocks, e.g. in var/logs/block.log
$cachingConfiguration['Block.LogFile'] = '';
// Size in bytes to limit the block cache to
$cachingConfiguration['Block.SizeLimit'] = 2097152;

// Maximum life time of module cache in seconds
$cachingConfiguration['Module.TimeExpiration'] = 7200;
// Store the cached modules in filesystem, database or memcache
$cachingConfiguration['Module.CacheStorage'] = 'filesystem';
// Keep a logfile of the cache hits and misses for modules, e.g. in var/logs/module.log
$cachingConfiguration['Module.LogFile'] = '';
// Size in bytes to limit the module cache to
$cachingConfiguration['Module.SizeLimit'] = 2097152;
// Default cache settings for module functions
$cachingConfiguration['Module.CacheFunctions'] = array('main' => 1, 'view' => 1, 'display' => 0);

// Maximum life time of object cache in seconds
$cachingConfiguration['Object.TimeExpiration'] = 7200;
// Store the cached objects in filesystem, database or memcache
$cachingConfiguration['Object.CacheStorage'] = 'filesystem';
// Keep a logfile of the cache hits and misses for objects, e.g. in var/logs/object.log
$cachingConfiguration['Object.LogFile'] = '';
// Size in bytes to limit the object cache to
$cachingConfiguration['Object.SizeLimit'] = 2097152;
// Default cache settings for object methods
$cachingConfiguration['Object.CacheMethods'] = array('view' => 1, 'display' => 1);

?>
