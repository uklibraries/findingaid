## findingaid

This is a finding aid viewer intended to be used as part of a digital library.
It is written to run on Linux and has not been tested with other operating
systems, but should run on most Unix-like operating systems.

## Caveat

This is written specifically for the University of Kentucky Libraries and
includes some highly local assumptions. You are welcome to use and reuse this
code according to the terms of the license, but you should be aware of its
opinionated nature.

## Developer installation

Developer installations have been tested on Linux (through Windows with
[WSL](https://learn.microsoft.com/en-us/windows/wsl/)) and macOS.

### Quickstart

```shell
git clone https://github.com/uklibraries/findingaid.git
cd findingaid
make sample
make dev
```

The application should then be available at `http://localhost:8080/<id>`.
Developers should run `make help` to see a list of helper commands through
[make](https://www.gnu.org/software/make/).

### Dependencies

We use [Docker](https://www.docker.com/) for reproducible environments.
Developers will want to consult the
[docker documentation for installation](https://docs.docker.com/engine/install/).
We make use of [make](https://www.gnu.org/software/make/) to manage commands,
which is a standard Linux utility and an old (but functional) version is
included with macOS. Developers can also optionally use
[watchexec](https://github.com/watchexec/watchexec) to run tests on every file
change, which will require separate installation. [Homebrew](https://brew.sh/)
is a recommended package manager that works for both Linux and macOS. Using
Docker requires access to a Linux kernel. Mac users should consider using
[Colima](https://github.com/abiosoft/colima) to access a Linux kernel. Windows
users should strongly consider working in WSL.

```
# macOS
brew install docker

# macOS optional
brew install make watchexec
```

### Sample data

Finding aids must be arranged in a
[PairTree](https://confluence.ucop.edu/display/Curation/PairTree) hierarchy in
the `xml` directory. The files are not pure EAD, but must be preprocessed using
a different program. For an example, install the
[sample data](https://exploreuk.uky.edu/fa/findingaid/xml.tar.gz) (which expands
to just shy of a gigabyte):

```shell
make sample
```

## Coding standard

This program attempts to adhere to the
[PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard for all PHP code.
For convenience, the dev environment provides
[PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer/), which
detects and can repair many PSR-12 violations. Developers can use `make lint` to
get a report of linting violations, and `make lint-fix` to fix those that can be
automatically fixed. These deliberately exclude line length as a fix.

## Contributors
Sarah Dorpinghaus, Neal Powers, Nicole Sand, and MLE Slone. For details, consult [CONTRIBUTORS](CONTRIBUTORS.md).

## Copyright

This program is Copyright (C) 2016-2026 University of Kentucky. For
details, consult [LICENSE](LICENSE.txt).

This program uses the following libraries:

- Luis Almeida's [unveil.js](https://github.com/luis-almeida/unveil)
- John Dyer's [MediaElement.js](http://mediaelementjs.com)
- Marcus Ekwall's [reveal.js](http://stackoverflow.com/a/7031800/237176)
- Justin Hileman's [Mustache.php](https://github.com/bobthecow/mustache.php)
- Jan Sorgalla's [Lity](http://sorgalla.com/lity/)
- [Bootstrap](https://getbootstrap.com)
- [jQuery](https://jquery.org)
- [jQuery UI](https://jqueryui.com)

