# strong-lumen

Lumen, but with a bunch of security-centric features

LAST AUDIT: December 2017

# Middleware

We added modern security Middleware for Lumen to ensure our APIs are a litte
more hardened than a default install.

The internet is a dangerous place, and non-security minded developers often
make mistakes that could easily be avoided.

## App ID

This is the Identifier someone needs to send through to access your application.

This allows you to add a layer of annoyance to endpoints that do not need authentication,
for example, endpoints that provide certain variables to applications, but that you do
not want to hardcode into your applications.

It's also useful for providing different configuration information from a common
endpoint based on the application in question, useful for things like white labels.

Set `APP_ID` in your .env and wrap your route in the middleware.

Example HTTP Header

```
App: 8A53A5C5-8B3D-4624-ACFA-C14945EC4F88
```

## Registration Access Key

Use this to limit access to registration endpoints to add a layer of annoyance.

This is useful for allowing endpoints for newsletter signups etc to know a key
before being able to submit.

Set `REGISTRATION_ACCESS_KEY` in your .env and wrap your route in the middleware.

Example HTTP Header

```
Registration-Access-Key: 8647032F-AFA3-4EB1-ABEA-B0A517394A2B
```

## Throttle

Allows you to set limits per route as to how many requests may happen.

This is useful for mitigating DDoS, Brute Force, and Flooding style attacks.

`'throttle:3,1'` means 3 requests per minute. `'throttle:300,1'` means 300.

Certain common routes have default throttles.

Responds with headers indicting how manuy requests are left on these routes,
and information about when bans expire.

Before Limit:

```
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 5
```

After Limit you get a `429 Too Many Requests`, and the Response Body contains

```
Too many consecutive attempts. Try again in 5s
```

## Content Security Policy

Includes a sane CSP, wrapping the entire application.

Currently adds

```
Content-Security-Policy: default-src 'none', connect-src 'self', 'upgrade-insecure-requests';
```

## Common Security Headers

Includes a set of Common security headers for browsers that support them.

Usefult for defense against XSS, Clickjacking, Content Type attacks,
Framable responses, and UI Redressing.

Also enables strict TLS.

Currently adds

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=7776000; includeSubDomains
```

## No Cache Headers

Disables caching

```
Cache-Control: no-cache, must-revalidate
```

## Server Header

Adds information about the server.

Useful for overriding and obscuring the name of the technology running the web
server.

```
Server: APP_NAME (APP_VERSION)
X-Powered-By: APP_NAME (APP_VERSION)
```

Requires APP_NAME and APP_VERSION set in the .env file.

## CORS

Adds support for Cross Origin Resource Sharing.

See `config/cors.php` for all options.

Defaults to:

```
    'supportsCredentials' => true,
    'allowedOrigins' => ['*'],
    'allowedHeaders' => ['Content-Type', 'Content-Length', 'Origin', 'X-Requested-With', 'Debug-Token', 'Registration-Access-Key', 'X-CSRF-Token', 'App', 'User-Agent', 'Authorization'],
    'allowedMethods' => ['GET', 'POST', 'PUT',  'DELETE', 'OPTIONS'],
    'exposedHeaders' => ['Authorization'],
    'maxAge' => 0,
```

Should support OPTIONS Preflight with Authorization header.

# JWT
