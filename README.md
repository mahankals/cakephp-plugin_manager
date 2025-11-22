<!--
/** replace bellow details */
{git-user}
{git-repo}
{git-branch}
{Package-Title}
{auther}
{author-site}
{email}
-->

# {Package-Title}

[![License: MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Latest Version](https://img.shields.io/github/v/tag/{git-user}/{git-repo}?label=Git%20Latest)](https://github.com/{git-user}/{git-repo})
[![Stable Version](https://img.shields.io/github/v/release/{git-user}/{git-repo}?label=Git%20Stable&sort=semver)](https://github.com/{git-user}/{git-repo}/releases)
[![Total Downloads](https://img.shields.io/github/downloads/{git-user}/{git-repo}/total?label=Git%20Downloads)](https://github.com/{git-user}/{git-repo}/releases)

[![GitHub Stars](https://img.shields.io/github/stars/{git-user}/{git-repo}?style=social)](https://github.com/{git-user}/{git-repo}/stargazers)
[![GitHub Forks](https://img.shields.io/github/forks/{git-user}/{git-repo}?style=social)](https://github.com/{git-user}/{git-repo}/network/members)
[![GitHub Watchers](https://img.shields.io/github/watchers/{git-user}/{git-repo}?style=social)](https://github.com/{git-user}/{git-repo}/watchers)


<!-- packagist details
[![Latest Stable Version](https://poser.pugx.org/{git-user}/{git-repo}/v/stable)](https://packagist.org/packages/{git-user}/{git-repo})
[![Total Downloads](https://poser.pugx.org/{git-user}/{git-repo}/downloads)](https://packagist.org/packages/{git-user}/{git-repo})
-->

Package description

---

## Features

- feature 1

## Installation

You can install this plugin directly from GitHub using Composer:

1. Add the GitHub repository to your app's `composer.json`:

   ```json
   "repositories": [
       {
           "type": "vcs",
           "url": "https://github.com/{git-user}/{git-repo}"
       }
   ]
   ```

1. Require the plugin via Composer:

   ```bash
   composer require mahankals/{git-repo}:dev-{git-branch}
   ```

1. Load the plugin

   **Method 1: from terminal**

   ```bash
   bin/cake plugin load {Package-Title}
   ```

   **Method 2: load in `Application.php`, bootstrap method**

   ```bash
   $this->addPlugin('{Package-Title}');
   ```

## Other Details


## Contributing

Contributions, issues, and feature requests are welcome!

## Author

[{author}]({author-site})

## License

This library is available as open source under the terms of the [MIT License](https://opensource.org/licenses/MIT).
