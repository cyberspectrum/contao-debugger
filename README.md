[![Version](http://img.shields.io/packagist/v/cyberspectrum/contao-debugger.svg?style=flat-square)](https://packagist.org/packages/cyberspectrum/contao-debugger)
[![Stable Build Status](http://img.shields.io/travis/cyberspectrum/contao-debugger/master.svg?style=flat-square)](https://travis-ci.org/cyberspectrum/contao-debugger)
[![License](http://img.shields.io/packagist/l/cyberspectrum/contao-debugger.svg?style=flat-square)](http://opensource.org/licenses/LGPL-3.0)
[![Downloads](http://img.shields.io/packagist/dt/cyberspectrum/contao-debugger.svg?style=flat-square)](https://packagist.org/packages/cyberspectrum/contao-debugger)

Debugger integration for Contao Open Source CMS.
================================================

This projects utilizes the great [debug bar](https://github.com/maximebf/php-debugbar) by [maximebf](https://github.com/maximebf).

It replaces the Contao internal debug bar entirely and provides several debugging inspections, all without the need to
install any PHP extension or the like.

Installation
------------

Add to your `composer.json` in the `require` section:
```
"cyberspectrum/contao-debugger": "~1.0"
```

Usage
-----

Simply enable the Contao debug mode as usual and you are all set. You will see a small icon on the bottom left corner in
the front and backend of Contao.
Click on it and the debug bar will expand.
