<?php
// Barebones caching config variables - 
// The idea is to keep the overhead low when doing page
// level caching.

// Flag files to enable caching:
// sys::varpath() . '/cache/output/cache.touch' : activate output caching in general
// sys::varpath() . '/cache/output/cache.pagelevel' : enable page caching
// sys::varpath() . '/cache/output/cache.blocklevel' : enable block caching

$cachingConfiguration = array();

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
?>
