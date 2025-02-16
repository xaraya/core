# Xaraya Core Services

## Past: Static method calls (2.4.x)

The switch-over to [core classes & methods](https://github.com/xaraya/core/wiki/New-Core-Features#oop-replacing-core-functions--constants-with-class-methods--constants) started in Xaraya 2.x allowed us to access core features in a consistent way.

Unfortunately, the systematic use of static method calls has its own problems. The current core classes are not simply facades ([laravel-style](https://laravel.com/docs/11.x/facades)) acting as static proxies, but they use real static methods, which becomes a problem to retain context and allow flexibility.

Some of the core classes have now been converted to act more like proxies, but this is a cumbersome task and it doesn't really solve the underlying issues.

## Present: Experiment with core trait (2.6.x)

On the module side, we can now migrate from procedural module functions to object-oriented module methods - see [Xaraya Modules](https://github.com/mikespub/xaraya-core/tree/com.xaraya.core.bermuda/html/lib/xaraya/modules).

This is the perfect opportunity to provide *Core Services* to these methods, and remove the tight coupling with the Xaraya core classes.

The first step was to provide instance methods like `$this->fetch()` that would map to underling `xarVar::fetch()` calls for the most commonly used static method calls via a single `CoreTrait`.

However that quickly became un-manageable, not only for the number of core methods to support, but also because they could "pollute" and be accidently overridden in module classes or method classes.

## Future: Using core services (2.7.x)

So now we have a limited number of core services that are made available like `$this->var()`, and each supports its own methods like `$this->var()->fetch()`. It's a slightly more verbose way than `xarVar::fetch()`, but it allows us to do some future changes on the core side without affecting the modules and vice-versa.

```
Available services:
- $this->ctl() = xarController::* Main Controller (getURL, redirect, ...)
- $this->log() = xarLog::* Logger (message, variable, ...)
- $this->mls() = xarMLS::* Multi-Language System (translate, ...)
- $this->mod() = xarMod*::* Modules (getVar, setVar, ...)
- $this->sec() = xarSec::* Security (checkAccess, genAuthKey, ...)
- $this->tpl() = xarTpl::* Templating (module, setPageTitle, ...)
- $this->var() = xarVar::* Variables (fetch, check, ...)
- $this->block() = xarBlock*::* Blocks (template, ...)
- $this->data() = DataObjectFactory::* with context (getObject, getObjectList, ...)
- $this->prop() = DataProperty*::* with context (getProperty, template, ...)
- $this->cache() = xar*Cache::* Caching (getModuleKey, getObjectKey, ...)
- $this->config() = xarConfigVars::* Config (getVar, setVar, ...)
- $this->session() = xarSession::* Session (getVar, setVar, ...)
- $this->db() = xarDB::* Database (getConn, getPrefix, ...)
- ...
- $this->ml($rawstring, ...$args) = short-hand version for $this->mls()->translate()
- $this->exit($status = 0) = call exit() - override for non-blocking servers, php unit tests or elsewhere
```

The good news is that we could re-use the same mechanism in other places like ui handlers, hook observers, objects & properties etc. So ideally most of "end-user" modules should be able to get rid of static core methods calls, except perhaps for special cases like initialization or module configuration.

To be continued...
