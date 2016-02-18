findingaid
==========

This is a finding aid viewer intended to be used as part of a digital library.
It is written to run on Linux and has not been tested with other operating
systems, but should run on most Unix-like operating systems.

Caveat
------

This is written specifically for the University of Kentucky Libraries and includes
some highly local assumptions.  You are welcome to use and reuse this code according
to the terms of the license, but you should be aware of its opinionated nature.

Dependencies
------------

* [Composer](https://getcomposer.org)

* [JSMin](https://github.com/douglascrockford/JSMin)

Installation
------------

1. Install the dependencies.

2. Extract the repository.

    ```shell
    git clone https://github.com/uklibraries/findingaid.git /path/to/findingaid
    ```

3. Descend into the findingaid repository.

    ```shell
    cd /path/to/findingaid
    ```

4. Use Composer to install needed packages.

5. Generate minified JavaScript.

    ```shell
    bash exe/build.sh
    ```

6. Install your finding aid data set in the xml directory.  The finding aids
must be arranged in a
[PairTree](https://confluence.ucop.edu/display/Curation/PairTree) hierarchy.
The files are not pure EAD, but must be preprocessed using a different
program.  For an example, install the
[sample data](https://nyx.uky.edu/fa/findingaid/xml.tar.gz)
(which expands to just shy of a gigabyte):

    ```shell
    wget https://nyx.uky.edu/fa/findingaid/xml.tar.gz
    tar zxf xml.tar.gz
    ```

7. Connect the public directory to your website.  For example, if your
website uses `/var/www/html` as its document root and permits symlinks, then
the following commands should make the finding aid viewer accessible.  Some
commands might require you to become root.

    ```shell
    cd /var/www/html
    ln -s /path/to/findingaid/public ./findingaid
    ```

Copyright
---------

This program is Copyright (C) 2016 MLE Slone.  For details, consult
LICENSE.txt.

This program uses Tim Down's [Rangy](https://github.com/timdown/rangy) library.
