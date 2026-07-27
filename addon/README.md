Checkmark Add-ons
=================

Install Checkmark add-on subplugins in this directory. Each add-on lives in its
own subdirectory and uses the Frankenstyle component name
`checkmark_<name>`.

Example:

```text
mod/checkmark/addon/randomselect/version.php
mod/checkmark/addon/randomselect/lang/en/checkmark_randomselect.php
```

The add-on must at least provide a `version.php` file with
`$plugin->component = 'checkmark_<name>';`.
