# ITK Video

Module that supplies video integration through a dedicated itk_video field
type.

- Supports [CookieInformation](https://cookieinformation.com)
- Supports [Videotool](https://videotool.dk/)
- Supports [Vimeo](https://vimeo.com/)

## How it works

The supported video providers are defined in [SupportedVideoProvider.php](src/SupportedVideoProvide.php).
The definitions include

- host: Used to identify the provider from the supplied video URL.
- type: Used to determine if videoinformation should be retrieved bu this
module (custom) or Oembed
- requiredCookies: The cookies that the provider sets when embedding a video
from that provider. See [CookieInformation](https://support.cookieinformation.com/en/articles/5444629-block-third-party-cookies-with-a-script)
about what categories are used.

If a provider is enabled in the module settings videos from this provider are
displayed.

## How to use

The module includes a permission to access the configuration page: "Administer ITK Video settings"

If allowed access the settings are set on `/admin/config/media/itk-video`.
Settings include:

- Respect limitations provided by cookieinformation.com
- Enable/disable supported video providers

## Code

To check coding standards run the following commands:

Install dependencies:

```bash
docker compose run --rm phpfpm composer install
```

Check composer and security updates:

```bash
docker compose run --rm phpfpm composer normalize --dry-run
docker compose run --rm phpfpm composer audit
```

Check assets:

```bash
docker compose run --rm prettier 'css/**/*.css' --check
```

Check php, code sniffer and code-analysis:

```bash
docker compose run --rm phpfpm vendor/bin/phpcs
./scripts/code-analysis
```

Check markdown and yaml:

```bash
docker compose run --rm markdownlint markdownlint '**/*.md'
docker compose run --rm prettier '**/*.{yml,yaml}' --check
```
