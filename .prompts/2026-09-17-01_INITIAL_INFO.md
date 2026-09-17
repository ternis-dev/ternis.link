# ternis.link
## A Project and Service by ternis-edv.de (ternis.dev)
### Other ternis related stuff: ternis.org ; dnbx.de ; ternisdomains.de ; tstatus.de ; example-dns.com(and .net and .org) ; mail-free.eu and mail-free.uk (Currently DOmains only, no platform or Service yet) ; 

## Domains (the ones by ternis-edv)
- ternis.link
- href.re
- href.nz
- *.ternis.link
- *.href.re
- *.href.nz
- ~~short.static.re~~
- links.thosted.de
- short.thosted.de
- go.thosted.de
- go.ternis.net
- go.ternis.dev
- go.ternis.org
- go.ternis.eu
- links.t-api.de (API ONLY)
- dash.ternis.link (Dashboard only)
- api.ternis.link (Redirects to links.t-api.de)

## Application
LaravelPHP-powered link-shortening and insights api focused on Analytics and "tracking"(referers)

## Features
- ternis.links and {name}.ternis.link is reserved for ternis family members and relatives as well as ternis pertners
- href.re is reserved for ternis's official "buiness"
- href.nz is teh "public" one. (/url/{url} will directly redirect ; /{link_slug} will redirect to the shortened url ; everyone can create short-links without api-key, api-key allows for shorter links and "plans" give even more as well as custom subdomain)
- links.thosted.de and short.thosted.de (and go.thosted.de) are also used for ternis-hosted related things only
- every "redirect" gets stored in db but href.nz/url/* redirects are only visible to admins ...
- "pertners" can also add their own domains/subdomains 
- api-url: links.t-api.de/v{version_id} ("/" redirects to teh latest version)
- alternatives to href.nz/url/ are href.nz/go/ and href.nz/{url} but /url/ is prefered
- dashboard is at dash.ternis.link (and maybe admin-dashboard at admin.ternis.link)




## Stack
- Blade + Livewire
- VanillaCSS (nested CSS)
- Deployment on a Caddy Webserver
- Algorithm which detects if $url href.nz/{url} is a slug or a url

## Api Versioning
- Retired api keeps "functionality" (Controllers or Filestructure is named according to api-version ...)